<?php

namespace App\Http\Controllers\Motor\Escalas;

use App\Http\Controllers\Controller;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAntiguedadTramo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloAntiguedad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Modelos de antigüedad (ESCALA con tramos dinámicos / LINEAL). Una versión usada no se modifica: se crea otra. */
class ModelosAntiguedadController extends Controller
{
    public function index(): Response
    {
        $usos = DB::table('motor_convenio_modelo_antiguedad')->selectRaw('id_modelo_antiguedad, count(*) n')->groupBy('id_modelo_antiguedad')->pluck('n', 'id_modelo_antiguedad');

        return Inertia::render('Motor/Escalas/Antiguedad/Index', ['items' => MotorModeloAntiguedad::query()->withCount('tramos')->orderBy('codigo')->get()->map(fn ($m) => [
            'id' => $m->id_modelo_antiguedad, 'codigo' => $m->codigo, 'descripcion' => $m->descripcion, 'metodo' => $m->metodo_calculo, 'porcentaje_por_anio' => $m->porcentaje_por_anio, 'porcentaje_maximo' => $m->porcentaje_maximo,
            'version' => $m->version, 'estado' => $m->estado_validacion, 'activo' => (bool) $m->activo, 'tramos' => $m->tramos_count, 'convenios' => (int) ($usos[$m->id_modelo_antiguedad] ?? 0),
        ])->all()]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Escalas/Antiguedad/Form', ['modelo' => null, 'tramos' => []]);
    }

    public function edit(int $modelo): Response
    {
        $m = MotorModeloAntiguedad::query()->findOrFail($modelo);

        return Inertia::render('Motor/Escalas/Antiguedad/Form', ['modelo' => $m, 'tramos' => MotorAntiguedadTramo::query()->where('id_modelo_antiguedad', $modelo)->orderBy('orden')->get(['anios_desde', 'anios_hasta', 'porcentaje', 'orden'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$d, $tramos] = $this->validar($request, null);
        DB::transaction(function () use ($d, $tramos) {
            $m = MotorModeloAntiguedad::query()->create($d + ['usuario_id' => auth()->id()]);
            $this->guardarTramos($m->id_modelo_antiguedad, $tramos);
        });

        return redirect()->route('motor.escalas.antiguedad.index')->with('success', 'Modelo de antigüedad creado.');
    }

    public function update(Request $request, int $modelo): RedirectResponse
    {
        $m = MotorModeloAntiguedad::query()->findOrFail($modelo);
        [$d, $tramos] = $this->validar($request, $m);
        unset($d['codigo']);
        DB::transaction(function () use ($m, $d, $tramos) {
            $m->update($d);
            $this->guardarTramos($m->id_modelo_antiguedad, $tramos);
        });

        return redirect()->route('motor.escalas.antiguedad.index')->with('success', 'Modelo de antigüedad actualizado.');
    }

    public function destroy(int $modelo): RedirectResponse
    {
        MotorModeloAntiguedad::query()->whereKey($modelo)->update(['activo' => 0]);

        return redirect()->route('motor.escalas.antiguedad.index')->with('success', 'Modelo dado de baja (baja lógica).');
    }

    /** @return array{0: array<string, mixed>, 1: list<array<string, mixed>>} */
    private function validar(Request $request, ?MotorModeloAntiguedad $actual): array
    {
        $d = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:motor_modelos_antiguedad,codigo'.($actual !== null ? ",{$actual->id_modelo_antiguedad},id_modelo_antiguedad" : '')],
            'descripcion' => 'required|string|max:255', 'metodo_calculo' => 'required|in:ESCALA,LINEAL', 'porcentaje_por_anio' => ['nullable', 'regex:/^\d(\.\d{1,6})?$/'], 'porcentaje_maximo' => ['nullable', 'regex:/^\d(\.\d{1,6})?$/'],
            'version' => 'nullable|integer|min:1', 'estado_validacion' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO', 'activo' => 'boolean', 'observaciones' => 'nullable|string',
            'tramos' => 'array', 'tramos.*.anios_desde' => 'required|numeric|min:0', 'tramos.*.anios_hasta' => 'nullable|numeric|min:0', 'tramos.*.porcentaje' => ['required', 'regex:/^\d(\.\d{1,6})?$/'],
        ], ['codigo.required' => 'El código es obligatorio.', 'codigo.unique' => 'Ya existe un modelo con ese código.', 'tramos.*.porcentaje.required' => 'Cada tramo necesita su porcentaje (fracción, p. ej. 0.02).', 'tramos.*.anios_desde.required' => 'Cada tramo necesita años desde.']);
        $tramos = array_values($d['tramos'] ?? []);
        unset($d['tramos']);
        if ($d['metodo_calculo'] === 'LINEAL' && ($d['porcentaje_por_anio'] ?? null) === null) {
            abort(422, 'Un modelo LINEAL exige porcentaje por año.');
        }
        if ($d['metodo_calculo'] === 'ESCALA' && $tramos === []) {
            abort(422, 'Un modelo ESCALA exige al menos un tramo.');
        }
        usort($tramos, fn ($a, $b) => (float) $a['anios_desde'] <=> (float) $b['anios_desde']);
        foreach ($tramos as $i => $t) {
            if (isset($tramos[$i + 1]) && $t['anios_hasta'] !== null && $t['anios_hasta'] !== '' && (float) $t['anios_hasta'] >= (float) $tramos[$i + 1]['anios_desde']) {
                abort(422, 'Los tramos no pueden superponerse.');
            }
        }

        return [$d, $tramos];
    }

    private function guardarTramos(int $idModelo, array $tramos): void
    {
        MotorAntiguedadTramo::query()->where('id_modelo_antiguedad', $idModelo)->delete();
        foreach ($tramos as $i => $t) {
            MotorAntiguedadTramo::query()->create(['id_modelo_antiguedad' => $idModelo, 'anios_desde' => $t['anios_desde'], 'anios_hasta' => $t['anios_hasta'] === '' ? null : $t['anios_hasta'], 'porcentaje' => $t['porcentaje'], 'orden' => $i + 1]);
        }
    }
}
