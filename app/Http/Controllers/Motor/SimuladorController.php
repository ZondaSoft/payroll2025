<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\CalcularHaberes;
use App\Motor\Aplicacion\CasosDeUso\SimularLiquidacion;
use App\Motor\Aplicacion\DTO\ConfiguracionMotor;
use App\Motor\Aplicacion\DTO\SolicitudSimulacion;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Formulas\BaseConstruida;
use App\Motor\Dominio\Formulas\GrafoCalculo;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimuladorController extends Controller
{
    public function index(ProveedorEmpresas $empresas, ConfiguracionMotor $config): Response
    {
        return Inertia::render('Motor/Simulador/Index', [
            'empresas' => array_map(fn ($e) => ['value' => $e->id, 'label' => "{$e->codigo} — {$e->razonSocial}"], $empresas->todas()),
            'relaciones' => MotorRelacionLaboral::query()->where('estado', '!=', 'INCONSISTENTE_ORIGEN')->orderBy('empresa_id')->orderBy('legajo')
                ->get(['relacion_laboral_id', 'empresa_id', 'legajo', 'apellido_nombre', 'estado'])
                ->map(fn ($r) => ['value' => $r->relacion_laboral_id, 'empresa_id' => $r->empresa_id, 'label' => "{$r->legajo} — {$r->apellido_nombre}".($r->estado === 'BAJA' ? ' (baja)' : '')])->all(),
            'entornoDefault' => $config->entorno->value,
            'tiposLiquidacion' => TipoLiquidacion::opciones(),
            'periodoDefault' => now()->format('Y-m'),
        ]);
    }

    public function simular(Request $request, SimularLiquidacion $caso, ConfiguracionMotor $config): JsonResponse
    {
        $d = $request->validate([
            'relacion_laboral_id' => 'required|integer|exists:motor_relacion_laboral,relacion_laboral_id',
            'periodo' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'tipo_liquidacion' => 'required|in:NORMAL,SAC,FINAL,VACACIONES',
            'entorno' => 'nullable|in:PRUEBA,PRODUCCION',
            'dias_liquidados' => 'nullable|integer|min:1|max:31',
            'factor_jornada' => ['nullable', 'regex:/^\d(\.\d{1,4})?$/'],
        ], [
            'relacion_laboral_id.required' => 'Elegí la relación laboral a simular.',
            'relacion_laboral_id.exists' => 'La relación laboral no existe.',
            'periodo.regex' => 'El período debe tener formato AAAA-MM.',
        ]);

        $r = $caso->ejecutar(new SolicitudSimulacion(
            (int) $d['relacion_laboral_id'], Periodo::de($d['periodo']), TipoLiquidacion::from($d['tipo_liquidacion']),
            Entorno::from($d['entorno'] ?? $config->entorno->value), (int) ($d['dias_liquidados'] ?? 30), (string) ($d['factor_jornada'] ?? '1'),
        ));

        return response()->json($r->aArray());
    }

    /**
     * Los haberes calculados con las fórmulas publicadas, con la traza de cada
     * importe y el orden que resolvió el grafo.
     *
     * Responde las preguntas del gate: qué entró, qué dio, de qué depende y en qué
     * orden. Es lo que convierte un importe en algo que se puede discutir con el
     * cliente en vez de un número que el sistema afirma.
     */
    public function haberes(Request $request, CalcularHaberes $caso): JsonResponse
    {
        $d = $request->validate([
            'relacion_laboral_id' => 'required|integer|exists:motor_relacion_laboral,relacion_laboral_id',
            'periodo' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'dias_liquidados' => 'nullable|integer|min:1|max:31',
            'dias_efectivos' => 'nullable|integer|min:0|max:31',
        ], [
            'relacion_laboral_id.required' => 'Elegí la relación laboral a simular.',
            'periodo.regex' => 'El período debe tener formato AAAA-MM.',
        ]);

        $estado = $caso->ejecutar(
            (int) $d['relacion_laboral_id'],
            Periodo::de($d['periodo']),
            (int) ($d['dias_liquidados'] ?? 30),
            isset($d['dias_efectivos']) ? (int) $d['dias_efectivos'] : null,
        );

        $conceptos = [];
        foreach ($estado->conceptos as $codigo => $r) {
            $conceptos[] = [
                'codigo' => $codigo,
                'estado' => $r->estado,
                'variante' => $r->variante,
                'cantidad' => $r->cantidad === null ? null : (string) $r->cantidad,
                'valor_unitario' => $r->valorUnitario === null ? null : (string) $r->valorUnitario,
                'importe_bruto' => $r->importeBruto === null ? null : (string) $r->importeBruto,
                'importe' => $r->importeResultado === null ? null : (string) $r->importeResultado,
                'factor_signo' => $r->factorSigno,
                'impacta_neto' => $r->impactaNeto,
                'codigo_error' => $r->codigoError,
                'motivo' => $r->motivo,
                'traza' => $r->traza?->resumen() ?? [],
            ];
        }

        $bases = [];
        foreach ($estado->bases as $codigo => $b) {
            $bases[] = [
                'codigo' => $codigo,
                'estado' => $b->estado,
                'importe' => $b->importe === null ? null : (string) $b->importe,
                'incluidos' => $b->incluidos,
                'excluidos' => $b->excluidos,
                'motivo' => $b->motivo,
            ];
        }

        return response()->json([
            'conceptos' => $conceptos,
            'bases' => $bases,
            // El orden no se declara en ninguna parte: lo deriva el grafo de las
            // dependencias, y mostrarlo es la única forma de verificarlo.
            'orden' => array_map(fn (string $n) => [
                'nodo' => $n,
                'tipo' => str_starts_with($n, GrafoCalculo::BASE) ? 'BASE' : 'CONCEPTO',
                'codigo' => substr($n, 2),
            ], $estado->orden),
            'neto' => (string) $estado->neto(),
            'resumen' => [
                'calculados' => count($estado->porEstado(ResultadoConcepto::CALCULADO)),
                'no_aplican' => count($estado->porEstado(ResultadoConcepto::NO_APLICA)),
                'bloqueados' => count($estado->porEstado(ResultadoConcepto::BLOQUEADO)),
                'no_modelados' => count($estado->porEstado(ResultadoConcepto::NO_MODELADO)),
                'bases_ok' => count(array_filter($estado->bases, fn (BaseConstruida $b) => $b->esOk())),
                'bases_bloqueadas' => count(array_filter($estado->bases, fn (BaseConstruida $b) => ! $b->esOk())),
            ],
        ]);
    }
}
