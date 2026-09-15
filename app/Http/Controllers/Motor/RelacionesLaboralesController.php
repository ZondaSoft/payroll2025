<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\NormalizarNovedades;
use App\Motor\Aplicacion\CasosDeUso\SincronizarRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\RepositorioCatalogosArca;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Dominio\Entidades\CategoriaVigente;
use App\Motor\Dominio\Entidades\EncuadreArca;
use App\Motor\Dominio\Entidades\RegimenAsignado;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Enums\EstadoRelacion;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Relaciones laborales canónicas: listado, sincronización desde legajos, ficha con pestañas y edición de encuadre/regímenes/categoría. */
class RelacionesLaboralesController extends Controller
{
    public function __construct(private readonly RepositorioRelacionesLaborales $relaciones, private readonly ProveedorEmpresas $empresas, private readonly ProveedorCategorias $categorias) {}

    public function index(Request $request, ProveedorLegajos $legajos): Response
    {
        $q = MotorRelacionLaboral::query()->orderBy('empresa_id')->orderBy('legajo');
        if ($request->filled('empresa')) {
            $q->where('empresa_id', (int) $request->input('empresa'));
        }
        if ($request->filled('estado')) {
            $q->where('estado', $request->input('estado'));
        }
        if ($request->filled('buscar')) {
            $b = '%'.$request->input('buscar').'%';
            $q->where(fn ($w) => $w->where('legajo', 'like', $b)->orWhere('apellido_nombre', 'like', $b)->orWhere('cuil', 'like', $b));
        }
        $emp = [];
        foreach ($this->empresas->todas() as $e) {
            $emp[$e->id] = "{$e->codigo} — {$e->razonSocial}";
        }
        $items = $q->paginate(20)->withQueryString()->through(fn ($r) => ['id' => $r->relacion_laboral_id, 'legajo' => $r->legajo, 'nombre' => $r->apellido_nombre, 'cuil' => $r->cuil, 'empresa' => $emp[$r->empresa_id] ?? "#{$r->empresa_id}",
            'ingreso' => $r->fecha_ingreso, 'egreso' => $r->fecha_egreso, 'estado' => $r->estado]);
        $activos = count($legajos->activos());
        $vinculados = MotorRelacionLaboral::query()->where('estado', '!=', 'BAJA')->count();

        return Inertia::render('Motor/RelacionesLaborales/Index', ['items' => $items, 'filtros' => $request->only(['empresa', 'estado', 'buscar']),
            'empresas' => array_map(fn ($e) => ['value' => $e->id, 'codigo' => $e->codigo, 'label' => "{$e->codigo} — {$e->razonSocial}"], $this->empresas->todas()),
            'resumen' => ['legajos_activos' => $activos, 'relaciones_vigentes' => $vinculados, 'sin_sincronizar' => max(0, $activos - $vinculados)], 'estados' => EstadoRelacion::opciones()]);
    }

    public function show(int $relacion, RepositorioRegimenes $regimenes, RepositorioCatalogosArca $catalogos, RepositorioNovedades $novedades): Response
    {
        $rl = $this->relaciones->porId($relacion) ?? abort(404);
        $cats = $this->categorias->porIds(array_values(array_unique(array_filter(array_map(fn (CategoriaVigente $c) => $c->idCategoria, $rl->categorias)))));
        $empresa = $this->empresas->porId($rl->empresaId);
        $periodo = Periodo::deFecha(now()->toDateTimeImmutable());

        return Inertia::render('Motor/RelacionesLaborales/Show', [
            'relacion' => ['id' => $rl->id, 'legajo' => $rl->legajo, 'nombre' => $rl->apellidoNombre, 'cuil' => $rl->cuil, 'empresa' => $empresa?->razonSocial, 'empresa_id' => $rl->empresaId, 'id_legajo' => $rl->idLegajo,
                'ingreso' => $rl->fechaIngreso->format('Y-m-d'), 'egreso' => $rl->fechaEgreso?->format('Y-m-d'), 'antiguedad' => $rl->fechaAntiguedad?->format('Y-m-d'), 'estado' => $rl->estado->value, 'anios' => $rl->aniosAntiguedadA($periodo->ultimoDia())],
            'categorias' => array_map(fn (CategoriaVigente $c) => ['id' => $c->id, 'id_categoria' => $c->idCategoria, 'codigo' => $c->codigoCategoria, 'descripcion' => isset($cats[$c->idCategoria]) ? $cats[$c->idCategoria]->descripcion : null,
                'convenio' => $cats[$c->idCategoria]->codigoConvenio ?? null, 'desde' => $c->vigencia->desde->format('Y-m-d'), 'hasta' => $c->vigencia->hasta?->format('Y-m-d'), 'estado' => $c->estadoValidacion->value], $rl->categorias),
            'encuadre' => $rl->encuadreArca === null ? null : ['id' => $rl->encuadreArca->id, 'codigo_condicion' => $rl->encuadreArca->codigoCondicion, 'actividad_interna' => $rl->encuadreArca->actividadInterna, 'codigo_actividad_arca' => $rl->encuadreArca->codigoActividad,
                'codigo_modalidad' => $rl->encuadreArca->codigoModalidad, 'codigo_localidad_arca' => $rl->encuadreArca->codigoLocalidad, 'desde' => $rl->encuadreArca->vigencia->desde->format('Y-m-d'), 'estado' => $rl->encuadreArca->estadoValidacion->value],
            'regimenes' => array_map(fn (RegimenAsignado $r) => ['id' => $r->id, 'id_regimen' => $r->idRegimen, 'codigo' => $r->codigoRegimen, 'desde' => $r->vigencia->desde->format('Y-m-d'), 'hasta' => $r->vigencia->hasta?->format('Y-m-d'), 'estado' => $r->estadoValidacion->value, 'documento' => $r->documentoRespaldo], $rl->regimenes),
            'situaciones' => array_map(fn ($s) => ['periodo' => (string) $s->periodo, 'orden' => $s->orden, 'codigo' => $s->codigoSituacion, 'dia_desde' => $s->diaDesde, 'dia_hasta' => $s->diaHasta, 'origen' => $s->origen], $rl->situaciones),
            'novedades' => array_map(fn ($n) => ['codigo' => $n->codigoNovedad, 'cantidad' => $n->cantidad === null ? null : (string) $n->cantidad, 'valor' => $n->valor?->aTexto(2), 'origen' => $n->origen], $novedades->porPeriodoYRelaciones($periodo, [$rl->id])[$rl->id] ?? []),
            'periodo' => (string) $periodo,
            'catalogos' => [
                'categorias' => array_map(fn ($c) => ['value' => $c->id, 'label' => "{$c->codigo} — {$c->descripcion}"], $this->categorias->todas()),
                'condiciones' => array_map(fn ($c) => ['value' => $c, 'label' => $c], $catalogos->codigosCondiciones()), 'modalidades' => array_map(fn ($c) => ['value' => $c, 'label' => $c], $catalogos->codigosModalidades()),
                'actividades' => array_map(fn ($c) => ['value' => $c, 'label' => $c], $catalogos->codigosActividades()), 'localidades' => array_map(fn ($c) => ['value' => $c, 'label' => $c], $catalogos->codigosLocalidades()),
                'regimenes' => array_map(fn ($r) => ['value' => $r->id, 'label' => "{$r->codigo} — {$r->descripcion} ({$r->tipoRegla->value})"], $regimenes->todos()), 'estados' => EstadoRelacion::opciones(),
            ],
        ]);
    }

    /** Edición de la ficha: estado/fechas, nueva categoría (historiza), encuadre ARCA y regímenes (lista completa). */
    public function update(Request $request, int $relacion, RepositorioRegimenes $regimenes): RedirectResponse
    {
        $rl = $this->relaciones->porId($relacion) ?? abort(404);
        $d = $request->validate([
            'estado' => 'required|in:VIGENTE,BAJA,INCONSISTENTE_ORIGEN', 'fecha_egreso' => 'nullable|date_format:Y-m-d', 'fecha_antiguedad' => 'nullable|date_format:Y-m-d',
            'nueva_categoria' => 'nullable|array', 'nueva_categoria.id_categoria' => 'required_with:nueva_categoria|integer', 'nueva_categoria.desde' => 'required_with:nueva_categoria|date_format:Y-m-d',
            'encuadre' => 'nullable|array', 'encuadre.codigo_condicion' => 'required_with:encuadre|string', 'encuadre.codigo_modalidad' => 'required_with:encuadre|string', 'encuadre.codigo_actividad_arca' => 'nullable|string', 'encuadre.codigo_localidad_arca' => 'nullable|string', 'encuadre.actividad_interna' => 'nullable|string|max:100', 'encuadre.estado' => 'nullable|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
            'regimenes' => 'nullable|array', 'regimenes.*.id' => 'nullable|integer', 'regimenes.*.id_regimen' => 'required|integer', 'regimenes.*.desde' => 'required|date_format:Y-m-d', 'regimenes.*.hasta' => 'nullable|date_format:Y-m-d', 'regimenes.*.estado' => 'nullable|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO', 'regimenes.*.documento' => 'nullable|string|max:100',
            'categorias' => 'nullable|array', 'categorias.*.id' => 'required|integer', 'categorias.*.estado' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO', 'categorias.*.hasta' => 'nullable|date_format:Y-m-d',
        ]);

        $categorias = $rl->categorias;
        foreach ($d['categorias'] ?? [] as $c) {
            $categorias = array_map(fn (CategoriaVigente $x) => $x->id === (int) $c['id'] ? new CategoriaVigente($x->id, $x->idCategoria, $x->codigoCategoria, new Vigencia($x->vigencia->desde, isset($c['hasta']) ? new DateTimeImmutable($c['hasta']) : null), EstadoValidacion::from($c['estado'])) : $x, $categorias);
        }
        if (! empty($d['nueva_categoria'])) {
            $desde = new DateTimeImmutable($d['nueva_categoria']['desde']);
            $cat = $this->categorias->porIds([(int) $d['nueva_categoria']['id_categoria']])[(int) $d['nueva_categoria']['id_categoria']] ?? abort(422, 'La categoría no existe.');
            $categorias = array_map(fn (CategoriaVigente $x) => $x->vigencia->esAbierta() && $x->vigencia->desde < $desde ? new CategoriaVigente($x->id, $x->idCategoria, $x->codigoCategoria, $x->vigencia->cerradaAntesDe($desde), $x->estadoValidacion) : $x, $categorias);
            $categorias[] = new CategoriaVigente(null, $cat->id, $cat->codigo, Vigencia::abiertaDesde($desde), EstadoValidacion::SinConfirmar);
        }
        $encuadre = $rl->encuadreArca;
        if (! empty($d['encuadre'])) {
            $e = $d['encuadre'];
            $encuadre = new EncuadreArca($rl->encuadreArca?->id, $e['codigo_condicion'], $e['actividad_interna'] ?? null, $e['codigo_actividad_arca'] ?: null, $e['codigo_modalidad'], $e['codigo_localidad_arca'] ?: null,
                $rl->encuadreArca?->vigencia ?? Vigencia::abiertaDesde($rl->fechaIngreso), EstadoValidacion::from($e['estado'] ?? 'SIN_CONFIRMAR'));
        }
        $regs = $rl->regimenes;
        if (isset($d['regimenes'])) {
            $porId = [];
            foreach ($regimenes->todos() as $r) {
                $porId[$r->id] = $r;
            }
            $regs = [];
            foreach ($d['regimenes'] as $r) {
                $def = $porId[(int) $r['id_regimen']] ?? abort(422, 'Régimen inexistente.');
                $regs[] = new RegimenAsignado(isset($r['id']) ? (int) $r['id'] : null, $def->id, $def->codigo, Vigencia::de($r['desde'], $r['hasta'] ?? null), EstadoValidacion::from($r['estado'] ?? 'SIN_CONFIRMAR'), $r['documento'] ?? null);
            }
            $ids = array_filter(array_map(fn ($r) => $r->id, $regs));
            foreach ($rl->regimenes as $r) {
                if (! in_array($r->id, $ids, true)) {
                    \App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboralRegimen::query()->whereKey($r->id)->delete();
                }
            }
        }
        $this->relaciones->guardar(new RelacionLaboral($rl->id, $rl->idLegajo, $rl->empresaId, $rl->legajo, $rl->fechaIngreso, isset($d['fecha_egreso']) ? new DateTimeImmutable($d['fecha_egreso']) : null, EstadoRelacion::from($d['estado']),
            $rl->cuil, $rl->apellidoNombre, isset($d['fecha_antiguedad']) ? new DateTimeImmutable($d['fecha_antiguedad']) : null, $categorias, $encuadre, $regs, $rl->situaciones), auth()->id());

        return redirect()->route('motor.relaciones.show', $relacion)->with('success', 'Relación laboral actualizada.');
    }

    public function sincronizar(Request $request, SincronizarRelacionesLaborales $caso): JsonResponse
    {
        $d = $request->validate(['empresa' => 'nullable|string|max:2', 'periodo' => ['nullable', 'regex:/^\d{4}-\d{2}$/']]);
        $r = $caso->ejecutar($d['empresa'] ?? null, isset($d['periodo']) ? Periodo::de($d['periodo']) : null, null, auth()->id());

        return response()->json(['success' => true] + $r->aArray());
    }

    public function normalizarNovedades(Request $request, NormalizarNovedades $caso): JsonResponse
    {
        $d = $request->validate(['periodo' => ['required', 'regex:/^\d{4}-\d{2}$/']], ['periodo.required' => 'Indicá el período (AAAA-MM).']);
        $r = $caso->ejecutar(Periodo::de($d['periodo']), auth()->id());

        return response()->json(['success' => true] + $r->aArray());
    }
}
