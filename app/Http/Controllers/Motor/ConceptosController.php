<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\CompararVersion;
use App\Motor\Aplicacion\CasosDeUso\PublicarConcepto;
use App\Motor\Aplicacion\CasosDeUso\ValidarConcepto;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\ModoCalculo;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\PoliticaEdicion;
use App\Motor\Dominio\Enums\ReglaRedondeo;
use App\Motor\Dominio\Enums\Signo;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Enums\TipoCambio;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Enums\TipoRepeticion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoVersion;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorTipoNovedad;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/** ABM de conceptos con editor de 10 pestañas, validación, publicación por vigencia, versiones y diff. */
class ConceptosController extends Controller
{
    public function __construct(
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioVersiones $versiones,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly RepositorioBasesZonda $basesZonda,
        private readonly RepositorioAplicabilidad $aplicabilidad,
        private readonly RepositorioSubsistemas $subsistemas,
    ) {}

    public function index(Request $request, ValidarConcepto $validar): Response
    {
        $q = MotorConcepto::query()->orderBy('orden_visual_sugerido')->orderBy('codigo_concepto');
        if ($request->filled('buscar')) {
            $b = '%'.$request->input('buscar').'%';
            $q->where(fn ($w) => $w->where('codigo_concepto', 'like', $b)->orWhere('nombre_concepto', 'like', $b));
        }
        if ($request->filled('tipo')) {
            $q->where('tipo_concepto', $request->input('tipo'));
        }
        if ($request->filled('activo')) {
            $q->where('activo', (int) $request->input('activo'));
        }
        $versionesPorConcepto = MotorConceptoVersion::query()->selectRaw('id_concepto, count(*) n, max(numero_version) ultima')->groupBy('id_concepto')->get()->keyBy('id_concepto');
        $vigentes = $this->versiones->vigentesA(now()->toDateTimeImmutable());
        $items = $q->paginate(20)->withQueryString()->through(function ($m) use ($versionesPorConcepto, $vigentes, $validar) {
            $c = \App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios\RepositorioConceptosEloquent::mapear($m);
            $val = $c->activo ? $validar->ejecutar($c) : [];
            $errores = count(array_filter($val, fn ($v) => $v['nivel'] === 'ERROR'));
            $avisos = count($val) - $errores;

            return [
                'id' => $m->id_concepto, 'codigo' => $m->codigo_concepto, 'nombre' => $m->nombre_concepto, 'tipo' => $m->tipo_concepto, 'naturaleza' => $m->naturaleza,
                'estrategia' => $m->estrategia_calculo, 'modo' => $m->modo_calculo, 'activo' => (bool) $m->activo, 'estado_configuracion' => $m->estado_configuracion,
                'codigo_afip' => $m->codigo_afip, 'versiones' => (int) ($versionesPorConcepto[$m->id_concepto]->n ?? 0), 'ultima_version' => $versionesPorConcepto[$m->id_concepto]->ultima ?? null,
                'vigente' => isset($vigentes[$m->codigo_concepto]) ? $vigentes[$m->codigo_concepto]->numeroVersion : null,
                'semaforo' => $errores > 0 ? 'error' : ($avisos > 0 ? 'aviso' : 'ok'), 'errores' => $errores, 'avisos' => $avisos,
            ];
        });

        return Inertia::render('Motor/Conceptos/Index', [
            'items' => $items, 'filtros' => $request->only(['buscar', 'tipo', 'activo']),
            'resumen' => ['total' => MotorConcepto::query()->count(), 'activos' => MotorConcepto::query()->where('activo', 1)->count(),
                'sin_afip' => MotorConcepto::query()->whereNull('codigo_afip')->whereIn('naturaleza', ['REMUNERATIVO', 'NO_REMUNERATIVO'])->count(),
                'sin_version' => count($this->versiones->conceptosSinVersion())],
            'tipos' => TipoConcepto::opciones(),
        ]);
    }

    public function create(): Response
    {
        return $this->formulario(null);
    }

    public function edit(int $concepto): Response
    {
        return $this->formulario($this->conceptos->porId($concepto) ?? abort(404));
    }

    public function store(Request $request): RedirectResponse
    {
        $c = $this->conceptos->guardar($this->desdeRequest($request, null), auth()->id());
        $this->guardarRelacionados($request, $c);

        return redirect()->route('motor.conceptos.edit', $c->id)->with('success', "Concepto {$c->codigo} guardado como borrador. Validalo y publicalo cuando esté listo.");
    }

    public function update(Request $request, int $concepto): RedirectResponse
    {
        $actual = $this->conceptos->porId($concepto) ?? abort(404);
        $c = $this->conceptos->guardar($this->desdeRequest($request, $actual), auth()->id());
        $this->guardarRelacionados($request, $c);

        return redirect()->route('motor.conceptos.edit', $c->id)->with('success', "Concepto {$c->codigo} actualizado (borrador). Publicá una versión para que el motor lo use.");
    }

    public function destroy(int $concepto): RedirectResponse
    {
        $this->conceptos->darDeBaja($concepto);

        return redirect()->route('motor.conceptos.index')->with('success', 'Concepto dado de baja (baja lógica: los conceptos usados no se borran).');
    }

    public function validar(int $concepto, ValidarConcepto $caso): JsonResponse
    {
        $c = $this->conceptos->porId($concepto) ?? abort(404);

        return response()->json(['validaciones' => $caso->ejecutar($c)]);
    }

    public function publicar(Request $request, int $concepto, PublicarConcepto $caso): JsonResponse
    {
        $d = $request->validate([
            'vigencia_desde' => 'required|date_format:Y-m-d', 'vigencia_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:vigencia_desde',
            'motivo' => 'nullable|string|max:255', 'tipo_cambio' => 'nullable|in:ALTA,PARITARIA,NORMATIVO,CORRECCION,CAMBIO_FUNCIONAL,OTRO', 'forzar' => 'nullable|boolean',
        ], ['vigencia_desde.required' => 'Indicá desde cuándo rige la versión.', 'vigencia_hasta.after_or_equal' => 'Vigencia hasta no puede ser anterior a vigencia desde.']);
        try {
            $r = $caso->ejecutar($concepto, new DateTimeImmutable($d['vigencia_desde']), $d['motivo'] ?? null, isset($d['tipo_cambio']) ? TipoCambio::from($d['tipo_cambio']) : null, auth()->id(),
                isset($d['vigencia_hasta']) ? new DateTimeImmutable($d['vigencia_hasta']) : null, (bool) ($d['forzar'] ?? false));
        } catch (MotorBloqueado $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'codigo' => $e->codigo->value], 422);
        }

        return response()->json(['success' => true, 'sin_cambios' => $r['sin_cambios'], 'version' => $r['version'] === null ? null : ['numero' => $r['version']->numeroVersion, 'vigencia' => (string) $r['version']->vigencia, 'hash' => substr($r['version']->hash, 0, 12)], 'validaciones' => $r['validaciones']]);
    }

    public function versiones(int $concepto, CompararVersion $comparar): Response
    {
        $c = $this->conceptos->porId($concepto) ?? abort(404);
        $diff = $comparar->borradorContraVigente($concepto);

        return Inertia::render('Motor/Conceptos/Versiones', [
            'concepto' => ['id' => $c->id, 'codigo' => (string) $c->codigo, 'nombre' => $c->nombre, 'estado_configuracion' => $c->estadoConfiguracion->value],
            'versiones' => array_map(fn ($v) => ['id' => $v->id, 'numero' => $v->numeroVersion, 'desde' => $v->vigencia->desde->format('Y-m-d'), 'hasta' => $v->vigencia->hasta?->format('Y-m-d'),
                'motivo' => $v->motivo, 'tipo_cambio' => $v->tipoCambio?->value, 'usada' => $v->usadaEnLiquidacion, 'publicada' => $v->publicadaAt?->format('Y-m-d H:i'), 'hash' => substr($v->hash, 0, 12),
                'vigente_hoy' => $v->vigencia->contiene(now()->toDateTimeImmutable())], $this->versiones->deConcepto($concepto)),
            'diff_borrador' => ['identicos' => $diff->identicos, 'version_base' => $diff->versionBase, 'cambios' => $diff->cambios],
        ]);
    }

    public function diff(Request $request, int $concepto, CompararVersion $comparar): JsonResponse
    {
        $d = $request->validate(['a' => 'required|integer', 'b' => 'required|integer']);
        $r = $comparar->entreVersiones((int) $d['a'], (int) $d['b']);

        return response()->json(['codigo' => $r->codigoConcepto, 'a' => $r->versionBase, 'b' => $r->versionComparada, 'cambios' => $r->cambios, 'identicos' => $r->identicos]);
    }

    // ------------------------------------------------------------------

    private function formulario(?Concepto $c): Response
    {
        $codigo = $c === null ? null : (string) $c->codigo;
        $aplicaciones = $this->subsistemas->aplicaciones();
        $incid = [];
        if ($codigo !== null) {
            foreach ($this->incidencias->deConcepto($codigo) as $i) {
                if ($i->vigencia->contiene(now()->toDateTimeImmutable()) || $i->vigencia->desde > now()->toDateTimeImmutable()) {
                    $incid[$i->claveAplicacion()] = ['id' => $i->id, 'incide' => $i->incidencia->value, 'estado' => $i->estadoValidacion->value, 'desde' => $i->vigencia->desde->format('Y-m-d')];
                }
            }
        }
        $bz = [];
        if ($codigo !== null) {
            foreach ($this->basesZonda->incidenciasDeConcepto($codigo) as $i) {
                $bz[$i->codigoBaseZonda] = ['id' => $i->id, 'integra' => $i->integra, 'desde' => $i->vigencia->desde->format('Y-m-d'), 'hasta' => $i->vigencia->hasta?->format('Y-m-d')];
            }
        }
        $reglas = $codigo === null ? [] : array_map(fn (ReglaAplicabilidad $r) => ['id' => $r->id, 'origen' => $r->origen, 'tipo_ambito' => $r->ambito->value, 'id_convenio' => $r->idConvenio, 'id_categoria' => $r->idCategoria,
            'id_empresa' => $r->idEmpresa, 'relacion_laboral_id' => $r->relacionLaboralId, 'accion' => $r->accion->value, 'desde' => $r->vigencia->desde->format('Y-m-d'), 'hasta' => $r->vigencia->hasta?->format('Y-m-d'), 'estado' => $r->estadoValidacion->value], $this->aplicabilidad->deConcepto($codigo));

        return Inertia::render('Motor/Conceptos/Form', [
            'concepto' => $c === null ? null : $this->aVista($c),
            'incidencias_arca' => $incid, 'bases_zonda' => $bz, 'aplicabilidad' => $reglas,
            'catalogos' => [
                'tipos' => TipoConcepto::opciones(), 'naturalezas' => Naturaleza::opciones(), 'signos' => Signo::opciones(), 'modos' => ModoCalculo::opciones(),
                'estrategias' => EstrategiaCalculo::opciones(), 'fuentes' => FuenteComponente::opciones(), 'redondeos' => ReglaRedondeo::opciones(), 'politicas' => PoliticaEdicion::opciones(),
                'repeticiones' => TipoRepeticion::opciones(), 'tipos_liquidacion' => TipoLiquidacion::opciones(), 'tipos_cambio' => TipoCambio::opciones(), 'ambitos' => TipoAmbito::opciones(),
                'acciones' => AccionAplicabilidad::opciones(), 'meses' => ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'],
                'modulos' => ['MOTOR_GENERAL', 'SEGURIDAD_SOCIAL', 'OBRA_SOCIAL', 'ART', 'SCVO', 'SINDICATO', 'CONVENIO', 'GANANCIAS', 'OTRO'],
                'clasificaciones' => ['REMUNERACION', 'ADICIONAL', 'HORAS_EXTRAS', 'VIATICOS', 'AUSENTISMO', 'LICENCIA', 'DESCUENTO', 'CONTRIBUCION', 'INFORMATIVO', 'PENDIENTE_CLASIFICAR'],
                'parametros' => MotorParametro::query()->orderBy('codigo')->get(['codigo', 'descripcion'])->map(fn ($p) => ['value' => $p->codigo, 'label' => "{$p->codigo} — {$p->descripcion}"])->all(),
                'novedades' => MotorTipoNovedad::query()->orderBy('codigo')->get(['codigo', 'descripcion', 'unidad'])->map(fn ($n) => ['value' => $n->codigo, 'label' => "{$n->codigo} — {$n->descripcion} ({$n->unidad})"])->all(),
                'bases_zonda' => array_map(fn ($b) => ['value' => $b->codigo, 'label' => "{$b->codigo} — {$b->nombre}", 'activo' => $b->activo], $this->basesZonda->todas()),
                'bases_arca' => DB::table('motor_bases_arca')->orderBy('id_base')->get(['codigo_base', 'descripcion'])->map(fn ($b) => ['value' => $b->codigo_base, 'label' => "{$b->codigo_base} — {$b->descripcion}"])->all(),
                'aplicaciones' => array_map(fn ($a) => ['clave' => $a->clave(), 'subsistema' => $a->codigoSubsistema, 'tipo' => $a->tipoAplicacion->value, 'base' => $a->codigoBase, 'columna' => $a->columnaLsd, 'activo' => $a->activo], $aplicaciones),
                'convenios' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorConvenios::class)->todos()),
                'categorias' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorCategorias::class)->todas()),
                'empresas' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->razonSocial}"], app(ProveedorEmpresas::class)->todas()),
                'rangos_afip' => \App\Motor\Dominio\Servicios\ValidadorConcepto::RANGO_AFIP,
            ],
            'versiones' => $c === null ? [] : array_map(fn ($v) => ['numero' => $v->numeroVersion, 'desde' => $v->vigencia->desde->format('Y-m-d'), 'hasta' => $v->vigencia->hasta?->format('Y-m-d'), 'usada' => $v->usadaEnLiquidacion], $this->versiones->deConcepto($c->id)),
        ]);
    }

    /** @return array<string, mixed> */
    private function aVista(Concepto $c): array
    {
        return $c->aDefinicion() + ['id' => $c->id, 'activo' => $c->activo, 'estado_configuracion' => $c->estadoConfiguracion->value, 'orden_visual_sugerido' => $c->ordenVisual, 'observaciones_funcionales' => $c->observaciones,
            'vigencia_desde' => $c->vigencia->desde->format('Y-m-d'), 'vigencia_hasta' => $c->vigencia->hasta?->format('Y-m-d')];
    }

    private function desdeRequest(Request $request, ?Concepto $actual): Concepto
    {
        $d = $request->validate([
            'codigo_concepto' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:motor_conceptos,codigo_concepto'.($actual !== null ? ",{$actual->id},id_concepto" : '')],
            'nombre_concepto' => 'required|string|max:255', 'alias_recibo' => 'nullable|string|max:100', 'convenio_documental' => 'nullable|string|max:100',
            'tipo_concepto' => 'required|in:HABER,DESCUENTO,CONTRIBUCION,INFORMATIVO', 'naturaleza' => 'required|in:REMUNERATIVO,NO_REMUNERATIVO,DESCUENTO,CONTRIB_PATRONAL',
            'signo' => 'required|in:CREDITO,DEBITO,NEUTRO', 'unidad_principal' => 'nullable|string|max:20', 'modo_calculo' => 'required|in:FORMULA,NOVEDAD,IMPORTE_FIJO,PARAMETRO,TABLA,MODULO_SISTEMA,MANUAL',
            'modulo_responsable' => 'nullable|string|max:30', 'base_modulo' => 'nullable|string|exists:motor_bases_arca,codigo_base',
            'codigo_parametro' => 'nullable|string|exists:motor_parametros,codigo', 'codigo_novedad_cantidad' => 'nullable|string|exists:motor_tipos_novedad,codigo', 'codigo_novedad_importe' => 'nullable|string|exists:motor_tipos_novedad,codigo',
            'requiere_novedad' => 'boolean', 'editable_manual' => 'boolean', 'repetible' => 'boolean', 'permite_importe_cero' => 'boolean', 'imprime_recibo' => 'boolean', 'es_concepto_sistema' => 'boolean', 'activo' => 'boolean',
            'tipo_repeticion' => 'nullable|in:TODOS_LOS_MESES,CADA_N_MESES,DURANTE_N_MESES,MESES_ESPECIFICOS,SIN_RESTRICCION_PERIODICA', 'intervalo_meses' => 'nullable|integer|min:1', 'cantidad_meses' => 'nullable|integer|min:1',
            'meses_aplicacion' => 'nullable|array', 'periodo_inicio' => ['nullable', 'regex:/^\d{4}-\d{2}$/'], 'tipos_liquidacion' => 'nullable|array', 'tipos_liquidacion.*' => 'in:NORMAL,SAC,FINAL,VACACIONES',
            'estrategia_calculo' => 'nullable|in:IMPORTE_DIRECTO,CANTIDAD_X_VALOR,BASICO,ANTIGUEDAD,PORCENTAJE_SOBRE_BASE,MODULO_SISTEMA',
            'fuente_cantidad' => 'nullable|in:FORMULA,NOVEDAD,PARAMETRO,BASE_ZONDA,BASICO,MANUAL,MODULO_SISTEMA,NO_APLICA', 'fuente_valor' => 'nullable|in:FORMULA,NOVEDAD,PARAMETRO,BASE_ZONDA,BASICO,MANUAL,MODULO_SISTEMA,NO_APLICA',
            'fuente_importe' => 'nullable|in:FORMULA,NOVEDAD,PARAMETRO,BASE_ZONDA,BASICO,MANUAL,MODULO_SISTEMA,NO_APLICA', 'codigo_base_zonda' => 'nullable|string|exists:motor_bases_zonda,codigo_base',
            'factor_valor' => ['nullable', 'regex:/^-?\d{1,12}([.,]\d{1,6})?$/'], 'importe_fijo' => ['nullable', 'regex:/^-?\d{1,12}([.,]\d{1,6})?$/'],
            'regla_redondeo' => 'nullable|in:SIN_REDONDEO,DECIMAL,ENTERO,PARAMETRIZADA', 'politica_edicion' => 'nullable|in:NO_EDITABLE,EDITABLE,EDITABLE_CON_JUSTIFICACION',
            'codigo_afip' => 'nullable|integer|min:100000|max:999999', 'clasificacion_funcional' => 'nullable|string|max:50', 'ausentismo_evitable' => 'nullable|in:NA,EVITABLE,NO_EVITABLE',
            'orden_calculo' => 'nullable|integer|min:0', 'orden_visual_sugerido' => 'nullable|integer|min:0', 'vigencia_desde' => 'required|date_format:Y-m-d', 'vigencia_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:vigencia_desde',
            'observaciones_funcionales' => 'nullable|string',
        ], [
            'codigo_concepto.required' => 'El código es obligatorio.', 'codigo_concepto.regex' => 'R8: el código solo admite letras, números y guión bajo.', 'codigo_concepto.unique' => 'R8: ya existe un concepto con ese código.',
            'nombre_concepto.required' => 'El nombre es obligatorio.', 'vigencia_desde.required' => 'La vigencia desde es obligatoria.', 'vigencia_hasta.after_or_equal' => 'R9: vigencia hasta no puede ser anterior a vigencia desde.',
            'codigo_parametro.exists' => 'V14: el parámetro no existe.', 'codigo_novedad_cantidad.exists' => 'V13: la novedad no existe.', 'codigo_base_zonda.exists' => 'V17: la base de Zonda no existe.',
        ]);
        $dec = fn ($v) => $v === null || $v === '' ? null : str_replace(',', '.', (string) $v);
        try {
            return new Concepto(
                id: $actual?->id, codigo: CodigoConcepto::de($d['codigo_concepto']), nombre: $d['nombre_concepto'], tipo: TipoConcepto::from($d['tipo_concepto']), naturaleza: Naturaleza::from($d['naturaleza']),
                signo: Signo::from($d['signo']), modoCalculo: ModoCalculo::from($d['modo_calculo']), vigencia: Vigencia::de($d['vigencia_desde'], $d['vigencia_hasta'] ?? null),
                activo: (bool) ($d['activo'] ?? true), estadoConfiguracion: $actual?->estadoConfiguracion ?? EstadoConfiguracion::Borrador,
                aliasRecibo: $d['alias_recibo'] ?? null, convenioDocumental: $d['convenio_documental'] ?? null, unidadPrincipal: $d['unidad_principal'] ?? null, moduloResponsable: $d['modulo_responsable'] ?? null,
                baseModulo: $d['base_modulo'] ?? null, codigoParametro: $d['codigo_parametro'] ?? null, codigoNovedadCantidad: $d['codigo_novedad_cantidad'] ?? null, codigoNovedadImporte: $d['codigo_novedad_importe'] ?? null,
                requiereNovedad: (bool) ($d['requiere_novedad'] ?? false), editableManual: (bool) ($d['editable_manual'] ?? false), repetible: (bool) ($d['repetible'] ?? false), permiteImporteCero: (bool) ($d['permite_importe_cero'] ?? false),
                imprimeRecibo: (bool) ($d['imprime_recibo'] ?? true), esConceptoSistema: (bool) ($d['es_concepto_sistema'] ?? false),
                tipoRepeticion: isset($d['tipo_repeticion']) ? TipoRepeticion::from($d['tipo_repeticion']) : null, intervaloMeses: $d['intervalo_meses'] ?? null, cantidadMeses: $d['cantidad_meses'] ?? null,
                mesesAplicacion: array_values($d['meses_aplicacion'] ?? []), periodoInicio: isset($d['periodo_inicio']) ? \App\Motor\Dominio\ValorObjetos\Periodo::de($d['periodo_inicio']) : null,
                tiposLiquidacion: empty($d['tipos_liquidacion']) ? null : array_map(fn ($t) => TipoLiquidacion::from($t), $d['tipos_liquidacion']),
                estrategia: isset($d['estrategia_calculo']) ? EstrategiaCalculo::from($d['estrategia_calculo']) : null, fuenteCantidad: isset($d['fuente_cantidad']) ? FuenteComponente::from($d['fuente_cantidad']) : null,
                fuenteValor: isset($d['fuente_valor']) ? FuenteComponente::from($d['fuente_valor']) : null, fuenteImporte: isset($d['fuente_importe']) ? FuenteComponente::from($d['fuente_importe']) : null,
                codigoBaseZonda: $d['codigo_base_zonda'] ?? null, factorValor: $dec($d['factor_valor'] ?? null) === null ? null : \Brick\Math\BigDecimal::of($dec($d['factor_valor'])),
                importeFijo: $dec($d['importe_fijo'] ?? null) === null ? null : \App\Motor\Dominio\ValorObjetos\Importe::de($dec($d['importe_fijo'])),
                reglaRedondeo: isset($d['regla_redondeo']) ? ReglaRedondeo::from($d['regla_redondeo']) : null, politicaEdicion: isset($d['politica_edicion']) ? PoliticaEdicion::from($d['politica_edicion']) : null,
                codigoAfip: $d['codigo_afip'] ?? null, clasificacionFuncional: $d['clasificacion_funcional'] ?? null, ausentismoEvitable: $d['ausentismo_evitable'] ?? null, ordenCalculo: $d['orden_calculo'] ?? null,
                ordenVisual: $d['orden_visual_sugerido'] ?? null, observaciones: $d['observaciones_funcionales'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    /** Incidencias ARCA (13 aplicaciones), composición de bases Zonda y reglas de aplicabilidad enviadas junto al concepto. */
    private function guardarRelacionados(Request $request, Concepto $c): void
    {
        $codigo = (string) $c->codigo;
        $desde = $c->vigencia->desde;
        $aplicaciones = [];
        foreach ($this->subsistemas->aplicaciones() as $a) {
            $aplicaciones[$a->clave()] = $a;
        }
        $existentes = [];
        foreach ($this->incidencias->deConcepto($codigo) as $i) {
            $existentes[$i->claveAplicacion()] = $i;
        }
        foreach ((array) $request->input('incidencias_arca', []) as $clave => $valor) {
            $ap = $aplicaciones[$clave] ?? null;
            if ($ap === null || ! in_array($valor, ['S', 'N', 'PENDIENTE'], true)) {
                continue;
            }
            $prev = $existentes[$clave] ?? null;
            if ($prev !== null && $prev->incidencia->value === $valor) {
                continue;
            }
            $this->incidencias->guardar(new IncidenciaArca($prev?->id, $codigo, $ap->id, $ap->codigoSubsistema, $ap->tipoAplicacion, Incidencia::from($valor), $prev?->vigencia ?? Vigencia::abiertaDesde($desde), EstadoValidacion::SinConfirmar), auth()->id());
        }

        $basesExistentes = [];
        foreach ($this->basesZonda->incidenciasDeConcepto($codigo) as $i) {
            $basesExistentes[$i->codigoBaseZonda] = $i;
        }
        $porCodigo = [];
        foreach ($this->basesZonda->todas() as $b) {
            $porCodigo[$b->codigo] = $b;
        }
        foreach ((array) $request->input('bases_zonda', []) as $codigoBase => $integra) {
            $b = $porCodigo[$codigoBase] ?? null;
            if ($b === null) {
                continue;
            }
            $prev = $basesExistentes[$codigoBase] ?? null;
            $flag = filter_var($integra, FILTER_VALIDATE_BOOLEAN);
            if ($prev !== null && $prev->integra === $flag) {
                continue;
            }
            $this->basesZonda->guardarIncidencia(new IncidenciaBaseZonda($prev?->id, $b->id, $codigoBase, $codigo, $flag, $prev?->vigencia ?? Vigencia::abiertaDesde($desde), EstadoValidacion::SinConfirmar), auth()->id());
        }

        $reglasEnviadas = (array) $request->input('aplicabilidad', []);
        $idsEnviados = array_filter(array_map(fn ($r) => $r['id'] ?? null, $reglasEnviadas));
        foreach ($this->aplicabilidad->deConcepto($codigo) as $r) {
            if (! in_array($r->id, $idsEnviados, true)) {
                $this->aplicabilidad->eliminar($r->id, $r->origen);
            }
        }
        foreach ($reglasEnviadas as $r) {
            $ambito = TipoAmbito::tryFrom((string) ($r['tipo_ambito'] ?? ''));
            $accion = AccionAplicabilidad::tryFrom((string) ($r['accion'] ?? 'INCLUIR'));
            if ($ambito === null || $accion === null) {
                continue;
            }
            $this->aplicabilidad->guardar(new ReglaAplicabilidad(isset($r['id']) ? (int) $r['id'] : null, $codigo, $ambito,
                isset($r['id_convenio']) && $r['id_convenio'] !== '' ? (int) $r['id_convenio'] : null, isset($r['id_categoria']) && $r['id_categoria'] !== '' ? (int) $r['id_categoria'] : null,
                isset($r['id_empresa']) && $r['id_empresa'] !== '' ? (int) $r['id_empresa'] : null, isset($r['relacion_laboral_id']) && $r['relacion_laboral_id'] !== '' ? (int) $r['relacion_laboral_id'] : null,
                $accion, Vigencia::de($r['desde'] ?? $desde->format('Y-m-d'), $r['hasta'] ?? null), EstadoValidacion::SinConfirmar), auth()->id());
        }
    }
}
