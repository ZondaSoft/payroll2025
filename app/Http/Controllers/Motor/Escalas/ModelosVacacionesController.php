<?php

namespace App\Http\Controllers\Motor\Escalas;

use App\Http\Controllers\Controller;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloVacaciones;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorVacacionesTramo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ModelosVacacionesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Motor/Escalas/Vacaciones/Index', ['items' => MotorModeloVacaciones::query()->withCount('tramos')->orderBy('codigo')->get()->map(fn ($m) => [
            'id' => $m->id_modelo_vacaciones, 'codigo' => $m->codigo, 'descripcion' => $m->descripcion, 'version' => $m->version, 'estado' => $m->estado, 'fuente' => $m->fuente, 'activo' => (bool) $m->activo, 'tramos' => $m->tramos_count,
        ])->all()]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Escalas/Vacaciones/Form', ['modelo' => null, 'tramos' => []]);
    }

    public function edit(int $modelo): Response
    {
        return Inertia::render('Motor/Escalas/Vacaciones/Form', ['modelo' => MotorModeloVacaciones::query()->findOrFail($modelo), 'tramos' => MotorVacacionesTramo::query()->where('id_modelo_vacaciones', $modelo)->orderBy('orden')->get(['anios_desde', 'incluye_desde', 'anios_hasta', 'incluye_hasta', 'dias', 'orden'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$d, $tramos] = $this->validar($request, null);
        DB::transaction(function () use ($d, $tramos) {
            $m = MotorModeloVacaciones::query()->create($d + ['usuario_id' => auth()->id()]);
            $this->guardarTramos($m->id_modelo_vacaciones, $tramos);
        });

        return redirect()->route('motor.escalas.vacaciones.index')->with('success', 'Modelo de vacaciones creado.');
    }

    public function update(Request $request, int $modelo): RedirectResponse
    {
        $m = MotorModeloVacaciones::query()->findOrFail($modelo);
        [$d, $tramos] = $this->validar($request, $m);
        unset($d['codigo']);
        DB::transaction(function () use ($m, $d, $tramos) {
            $m->update($d);
            $this->guardarTramos($m->id_modelo_vacaciones, $tramos);
        });

        return redirect()->route('motor.escalas.vacaciones.index')->with('success', 'Modelo de vacaciones actualizado.');
    }

    public function destroy(int $modelo): RedirectResponse
    {
        MotorModeloVacaciones::query()->whereKey($modelo)->update(['activo' => 0]);

        return redirect()->route('motor.escalas.vacaciones.index')->with('success', 'Modelo dado de baja (baja lógica).');
    }

    private function validar(Request $request, ?MotorModeloVacaciones $actual): array
    {
        $d = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:motor_modelos_vacaciones,codigo'.($actual !== null ? ",{$actual->id_modelo_vacaciones},id_modelo_vacaciones" : '')],
            'descripcion' => 'required|string|max:255', 'version' => 'nullable|integer|min:1', 'estado' => 'nullable|string|max:50', 'fuente' => 'nullable|string|max:100', 'activo' => 'boolean',
            'tramos' => 'required|array|min:1', 'tramos.*.anios_desde' => 'required|numeric|min:0', 'tramos.*.incluye_desde' => 'boolean', 'tramos.*.anios_hasta' => 'nullable|numeric|min:0', 'tramos.*.incluye_hasta' => 'nullable|boolean', 'tramos.*.dias' => 'required|integer|min:0',
        ], ['codigo.required' => 'El código es obligatorio.', 'tramos.required' => 'Cargá al menos un tramo.', 'tramos.*.dias.required' => 'Cada tramo necesita los días.']);
        $tramos = array_values($d['tramos']);
        unset($d['tramos']);
        usort($tramos, fn ($a, $b) => (float) $a['anios_desde'] <=> (float) $b['anios_desde']);

        return [$d, $tramos];
    }

    private function guardarTramos(int $idModelo, array $tramos): void
    {
        MotorVacacionesTramo::query()->where('id_modelo_vacaciones', $idModelo)->delete();
        foreach ($tramos as $i => $t) {
            MotorVacacionesTramo::query()->create(['id_modelo_vacaciones' => $idModelo, 'anios_desde' => $t['anios_desde'], 'incluye_desde' => (bool) ($t['incluye_desde'] ?? true), 'anios_hasta' => $t['anios_hasta'] === '' ? null : $t['anios_hasta'],
                'incluye_hasta' => isset($t['incluye_hasta']) ? (bool) $t['incluye_hasta'] : null, 'dias' => $t['dias'], 'orden' => $i + 1]);
        }
    }
}
