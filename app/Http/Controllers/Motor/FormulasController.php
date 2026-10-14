<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\RepositorioFormulas;
use App\Motor\Aplicacion\Servicios\FabricaCatalogoDsl;
use App\Motor\Dominio\Formulas\Dsl;
use App\Motor\Dominio\Formulas\ResultadoValidacion;
use App\Motor\Dominio\Formulas\ValidadorVersion;
use App\Motor\Dominio\Formulas\VersionFormula;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoVersion;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El editor de fórmulas: las cuatro expresiones de una versión, su validación
 * dimensional en vivo y la publicación con vigencia.
 *
 * La validación es la misma que corre al publicar (`ValidadorVersion`), no una
 * aproximación para la pantalla: lo que el editor muestra en verde es exactamente
 * lo que va a dejar publicar. En modo EDICION los avisos de migración y la unidad
 * sin declarar son advertencias; al publicar bloquean.
 */
class FormulasController extends Controller
{
    public function __construct(
        private readonly FabricaCatalogoDsl $catalogos,
        private readonly RepositorioFormulas $formulas,
        private readonly ValidadorVersion $validador = new ValidadorVersion,
    ) {}

    public function index(Request $request): Response
    {
        $fecha = $this->fecha($request);
        $catalogo = $this->catalogos->construir($fecha);
        $versiones = $this->formulas->vigentesA($fecha);

        $filas = [];
        foreach ($versiones as $v) {
            $r = $this->validador->validar($v, $catalogo);
            $filas[] = [
                'id' => $v->id,
                'concepto' => $v->codigoConcepto,
                'variante' => $v->variante,
                'metodo' => $v->metodo,
                'unidad' => (string) $r->unidadImporte,
                'publica' => $r->publica(),
                'errores' => count($r->errores),
                'advertencias' => count($r->advertencias),
                'codigos' => $r->codigos(),
                // La versión completa, para precargar el editor sin otra consulta: lo que
                // se edita es una copia de la vigente, nunca la vigente misma.
                'version' => [
                    'codigo_concepto' => $v->codigoConcepto,
                    'variante' => $v->variante,
                    'metodo' => $v->metodo,
                    'expr_condicion' => $v->exprCondicion ?? '',
                    'expr_cantidad' => $v->exprCantidad ?? '',
                    'expr_valor_unitario' => $v->exprValorUnitario ?? '',
                    'expr_importe' => $v->exprImporte ?? '',
                    'unidad_cantidad' => $v->unidadCantidad ?? '',
                    'codigo_modulo' => $v->codigoModulo ?? '',
                    'regla_redondeo' => $v->reglaRedondeo,
                    'novedades' => $v->novedades,
                    'justificaciones' => $v->justificaciones,
                ],
            ];
        }

        return Inertia::render('Motor/Formulas/Index', [
            'items' => $filas,
            'fecha' => $fecha->format('Y-m-d'),
            'resumen' => [
                'total' => count($filas),
                'publican' => count(array_filter($filas, fn (array $f) => $f['publica'])),
                'con_problemas' => count(array_filter($filas, fn (array $f) => ! $f['publica'])),
            ],
        ]);
    }

    /**
     * Analiza una versión completa y devuelve lo que el editor necesita mostrar: la
     * unidad de cada componente, los problemas con su código y las dependencias.
     *
     * No persiste nada: es el equivalente de «probar la fórmula antes de guardarla».
     */
    public function analizar(Request $request): JsonResponse
    {
        $d = $request->validate([
            'codigo_concepto' => 'required|string|max:50',
            'variante' => 'nullable|string|max:30',
            'metodo' => 'nullable|in:FORMULA,MODULO',
            'expr_condicion' => 'nullable|string|max:4000',
            'expr_cantidad' => 'nullable|string|max:4000',
            'expr_valor_unitario' => 'nullable|string|max:4000',
            'expr_importe' => 'nullable|string|max:4000',
            'unidad_cantidad' => 'nullable|string|max:20',
            'codigo_modulo' => 'nullable|string|max:60',
            'regla_redondeo' => 'nullable|string|max:20',
            'signo' => 'nullable|in:CREDITO,DEBITO,NEUTRO',
            'novedades' => 'array',
            'novedades.*' => 'in:REQUERIDA,OPCIONAL',
            'justificaciones' => 'array',
            'modo' => 'nullable|in:PUBLICACION,EDICION',
            'fecha' => 'nullable|date_format:Y-m-d',
        ]);

        $version = new VersionFormula(
            codigoConcepto: $d['codigo_concepto'],
            variante: ($d['variante'] ?? null) ?: 'UNICA',
            metodo: ($d['metodo'] ?? null) ?: VersionFormula::FORMULA,
            exprCondicion: $d['expr_condicion'] ?? null,
            exprCantidad: $d['expr_cantidad'] ?? null,
            exprValorUnitario: $d['expr_valor_unitario'] ?? null,
            exprImporte: $d['expr_importe'] ?? null,
            unidadCantidad: ($d['unidad_cantidad'] ?? null) ?: null,
            codigoModulo: ($d['codigo_modulo'] ?? null) ?: null,
            reglaRedondeo: ($d['regla_redondeo'] ?? null) ?: 'SIN_REDONDEO',
            signo: ($d['signo'] ?? null) ?: 'CREDITO',
            novedades: $d['novedades'] ?? [],
            justificaciones: $d['justificaciones'] ?? [],
        );

        $modo = $d['modo'] ?? ValidadorVersion::EDICION;
        $r = $this->validador->validar($version, $this->catalogos->construir($this->fecha($request)), $modo);

        return response()->json($this->aArray($r, $modo));
    }

    /** Publica la versión editada con su vigencia y su motivo. */
    public function publicar(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'codigo_concepto' => 'required|string|exists:motor_conceptos,codigo_concepto',
            'variante' => 'required|string|max:30',
            'metodo' => 'required|in:FORMULA,MODULO',
            'expr_condicion' => 'nullable|string|max:4000',
            'expr_cantidad' => 'nullable|string|max:4000',
            'expr_valor_unitario' => 'nullable|string|max:4000',
            'expr_importe' => 'nullable|string|max:4000',
            'unidad_cantidad' => 'nullable|string|max:20',
            'codigo_modulo' => 'nullable|string|max:60',
            'regla_redondeo' => 'required|in:SIN_REDONDEO,DECIMAL,ENTERO',
            'vigencia_desde' => 'required|date_format:Y-m-d',
            'motivo' => 'required|string|max:255',
            'fuente_normativa' => 'nullable|string',
            'novedades' => 'array',
            'novedades.*' => 'in:REQUERIDA,OPCIONAL',
            'justificaciones' => 'array',
        ], [
            'motivo.required' => 'El motivo es obligatorio: queda en el historial para explicar por qué cambió la regla.',
            'vigencia_desde.required' => 'La vigencia es obligatoria: una regla nueva no reescribe lo ya liquidado.',
        ]);

        $concepto = MotorConcepto::query()->where('codigo_concepto', $d['codigo_concepto'])->firstOrFail();

        $version = new VersionFormula(
            codigoConcepto: $d['codigo_concepto'],
            variante: $d['variante'],
            metodo: $d['metodo'],
            exprCondicion: $d['expr_condicion'] ?? null,
            exprCantidad: $d['expr_cantidad'] ?? null,
            exprValorUnitario: $d['expr_valor_unitario'] ?? null,
            exprImporte: $d['expr_importe'] ?? null,
            unidadCantidad: ($d['unidad_cantidad'] ?? null) ?: null,
            codigoModulo: ($d['codigo_modulo'] ?? null) ?: null,
            reglaRedondeo: $d['regla_redondeo'],
            signo: $concepto->signo,
            novedades: $d['novedades'] ?? [],
            justificaciones: $d['justificaciones'] ?? [],
        );

        // La puerta es la misma que valida el motor: lo que no publica acá no publica
        // en ningún lado.
        $r = $this->validador->validar($version, $this->catalogos->construir(new DateTimeImmutable($d['vigencia_desde'])));
        if (! $r->publica()) {
            return back()->withErrors([
                'expr_importe' => 'La versión no pasa la validación de publicación: '
                    .implode(' · ', array_map(fn (array $e) => $e['codigo'].' — '.$e['mensaje'], $r->errores)),
            ]);
        }

        DB::transaction(function () use ($concepto, $version, $d) {
            $ahora = now();
            $cierre = date('Y-m-d', strtotime($d['vigencia_desde'].' -1 day'));

            // La versión anterior de la misma variante no se toca más allá de cerrarle
            // la vigencia: lo liquidado con ella tiene que poder reproducirse.
            MotorConceptoVersion::query()
                ->where('id_concepto', $concepto->id_concepto)
                ->where('variante', $version->variante)
                ->whereNull('vigencia_hasta')
                ->where('vigencia_desde', '<', $d['vigencia_desde'])
                ->update(['vigencia_hasta' => $cierre, 'updated_at' => $ahora]);

            $numero = 1 + (int) MotorConceptoVersion::query()
                ->where('id_concepto', $concepto->id_concepto)->where('variante', $version->variante)->max('numero_version');

            $snapshot = MotorConceptoVersion::query()->where('id_concepto', $concepto->id_concepto)
                ->orderBy('id_version')->value('snapshot');

            $id = MotorConceptoVersion::query()->insertGetId([
                'id_concepto' => $concepto->id_concepto,
                'variante' => $version->variante,
                'metodo' => $version->metodo,
                'expr_condicion' => $version->exprCondicion,
                'expr_cantidad' => $version->exprCantidad,
                'expr_valor_unitario' => $version->exprValorUnitario,
                'expr_importe' => $version->exprImporte,
                'unidad_cantidad' => $version->unidadCantidad,
                'codigo_modulo' => $version->codigoModulo,
                'regla_redondeo_version' => $version->reglaRedondeo,
                'estado' => 'PUBLICADA',
                'estado_validacion' => 'SIN_CONFIRMAR',
                'numero_version' => $numero,
                'vigencia_desde' => $d['vigencia_desde'],
                'esquema_snapshot' => 1,
                'snapshot' => is_string($snapshot) ? $snapshot : json_encode($snapshot ?? ['concepto' => ['codigo_concepto' => $version->codigoConcepto]]),
                'hash' => hash('sha256', json_encode([$version->codigoConcepto, $version->variante, $d['vigencia_desde'], $version->exprImporte])),
                'motivo' => $d['motivo'],
                'tipo_cambio' => 'MODIFICACION',
                'fuente_normativa' => $d['fuente_normativa'] ?? null,
                'publicada_at' => $ahora,
                'usuario_id' => auth()->id(),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], 'id_version');

            foreach ($version->novedades as $codigo => $obligatoriedad) {
                DB::table('motor_concepto_version_novedad')->insert([
                    'id_version' => $id, 'codigo_novedad' => $codigo, 'obligatoriedad' => $obligatoriedad,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
            foreach ($version->justificaciones as $literal => $justificacion) {
                DB::table('motor_concepto_version_literal')->insert([
                    'id_version' => $id, 'literal' => (string) $literal, 'justificacion' => $justificacion,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        });

        return redirect()->route('motor.conceptos.edit', $concepto->id_concepto)
            ->with('success', "Versión publicada para {$version->codigoConcepto}/{$version->variante} desde {$d['vigencia_desde']}.");
    }

    /** Lo que el editor necesita del catálogo: qué referencias existen y con qué unidad. */
    public function catalogo(Request $request): JsonResponse
    {
        $c = $this->catalogos->construir($this->fecha($request));
        $lista = function (array $items): array {
            $out = [];
            foreach ($items as $codigo => $datos) {
                $out[] = ['codigo' => $codigo, 'unidad' => $datos['unidad'] ?? $datos['unidad_cantidad'] ?? null];
            }

            return $out;
        };

        return response()->json([
            'PARAM' => $lista($c->parametros),
            'NOV' => $lista($c->novedades),
            'VAR' => $lista($c->variables),
            'VALCAT' => $lista($c->valcat),
            'CONC' => $lista($c->conceptos),
            'BASE' => array_map(fn (string $b) => ['codigo' => $b, 'unidad' => 'ARS'], array_keys($c->bases)),
            'MOD' => $lista($c->modulos),
            'funciones' => array_keys(Dsl::FUNCIONES),
            'reservadas' => Dsl::RESERVADAS,
        ]);
    }

    /** @return array<string, mixed> */
    private function aArray(ResultadoValidacion $r, string $modo): array
    {
        return [
            'modo' => $modo,
            'publica' => $r->publica(),
            'unidad_importe' => (string) $r->unidadImporte,
            'errores' => $r->errores,
            'advertencias' => $r->advertencias,
            'codigos' => $r->codigos(),
            'sin_unidad' => $r->sinUnidad,
            'dependencias' => array_values(array_map(
                fn (array $d) => ['tipo' => $d['tipo'], 'codigo' => $d['codigo'], 'arista' => $d['arista']],
                $r->dependencias
            )),
            'unidades' => array_map(fn ($a) => (string) $a->unidad, $r->analisis),
        ];
    }

    private function fecha(Request $request): DateTimeImmutable
    {
        $f = $request->input('fecha');

        return $f ? new DateTimeImmutable($f) : new DateTimeImmutable;
    }
}
