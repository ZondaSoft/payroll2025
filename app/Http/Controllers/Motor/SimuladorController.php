<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\SimularLiquidacion;
use App\Motor\Aplicacion\DTO\ConfiguracionMotor;
use App\Motor\Aplicacion\DTO\SolicitudSimulacion;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
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
}
