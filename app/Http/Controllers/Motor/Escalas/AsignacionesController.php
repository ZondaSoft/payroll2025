<?php

namespace App\Http\Controllers\Motor\Escalas;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConvenioModeloAntiguedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConvenioModeloVacaciones;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloAntiguedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloVacaciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Asignación de modelos de antigüedad y vacaciones a convenios (legacy sue007s), con vigencia. */
class AsignacionesController extends Controller
{
    public function __construct(private readonly ProveedorConvenios $convenios) {}

    public function index(): Response
    {
        $conv = [];
        foreach ($this->convenios->todos() as $c) {
            $conv[$c->id] = "{$c->codigo} — {$c->descripcion}";
        }
        $ma = MotorModeloAntiguedad::query()->pluck('codigo', 'id_modelo_antiguedad');
        $mv = MotorModeloVacaciones::query()->pluck('codigo', 'id_modelo_vacaciones');

        return Inertia::render('Motor/Escalas/Asignaciones/Index', [
            'antiguedad' => MotorConvenioModeloAntiguedad::query()->orderBy('id_convenio')->orderByDesc('vigencia_desde')->get()->map(fn ($a) => ['id' => $a->id_asignacion_antiguedad, 'tipo' => 'ANTIGUEDAD', 'convenio' => $conv[$a->id_convenio] ?? "#{$a->id_convenio}", 'modelo' => $ma[$a->id_modelo_antiguedad] ?? '?', 'desde' => $a->vigencia_desde, 'hasta' => $a->vigencia_hasta, 'estado' => $a->estado_validacion])->all(),
            'vacaciones' => MotorConvenioModeloVacaciones::query()->orderBy('id_convenio')->orderByDesc('vigencia_desde')->get()->map(fn ($a) => ['id' => $a->id_asignacion_vacaciones, 'tipo' => 'VACACIONES', 'convenio' => $conv[$a->id_convenio] ?? "#{$a->id_convenio}", 'modelo' => $mv[$a->id_modelo_vacaciones] ?? '?', 'desde' => $a->vigencia_desde, 'hasta' => $a->vigencia_hasta, 'estado' => null, 'fuente' => $a->fuente])->all(),
            'convenios' => array_map(fn ($c) => ['value' => $c->id, 'label' => "{$c->codigo} — {$c->descripcion}"], $this->convenios->todos()),
            'modelos_antiguedad' => MotorModeloAntiguedad::query()->where('activo', 1)->get()->map(fn ($m) => ['value' => $m->id_modelo_antiguedad, 'label' => "{$m->codigo} — {$m->descripcion}"])->all(),
            'modelos_vacaciones' => MotorModeloVacaciones::query()->where('activo', 1)->get()->map(fn ($m) => ['value' => $m->id_modelo_vacaciones, 'label' => "{$m->codigo} — {$m->descripcion}"])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Escalas/Asignaciones/Form', ['asignacion' => null, 'tipo' => request('tipo', 'ANTIGUEDAD')] + $this->catalogos());
    }

    public function edit(Request $request, int $asignacion): Response
    {
        $tipo = $request->input('tipo', 'ANTIGUEDAD');
        $m = $tipo === 'VACACIONES' ? MotorConvenioModeloVacaciones::query()->findOrFail($asignacion) : MotorConvenioModeloAntiguedad::query()->findOrFail($asignacion);

        return Inertia::render('Motor/Escalas/Asignaciones/Form', ['asignacion' => $m, 'tipo' => $tipo] + $this->catalogos());
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request);
        $this->modelo($d['tipo'])::query()->create($this->datos($d) + ['usuario_id' => auth()->id()]);

        return redirect()->route('motor.escalas.asignaciones.index')->with('success', 'Asignación creada.');
    }

    public function update(Request $request, int $asignacion): RedirectResponse
    {
        $d = $this->validar($request);
        $this->modelo($d['tipo'])::query()->findOrFail($asignacion)->update($this->datos($d));

        return redirect()->route('motor.escalas.asignaciones.index')->with('success', 'Asignación actualizada.');
    }

    public function destroy(Request $request, int $asignacion): RedirectResponse
    {
        $this->modelo($request->input('tipo', 'ANTIGUEDAD'))::query()->whereKey($asignacion)->delete();

        return redirect()->route('motor.escalas.asignaciones.index')->with('success', 'Asignación eliminada.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => 'required|in:ANTIGUEDAD,VACACIONES', 'id_convenio' => 'required|integer', 'id_modelo' => 'required|integer', 'vigencia_desde' => 'required|date_format:Y-m-d',
            'vigencia_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:vigencia_desde', 'estado_validacion' => 'nullable|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO', 'fuente' => 'nullable|string|max:255',
        ], ['id_convenio.required' => 'Elegí el convenio.', 'id_modelo.required' => 'Elegí el modelo.', 'vigencia_hasta.after_or_equal' => 'Vigencia hasta no puede ser anterior a vigencia desde.']);
    }

    private function datos(array $d): array
    {
        $base = ['id_convenio' => $d['id_convenio'], 'vigencia_desde' => $d['vigencia_desde'], 'vigencia_hasta' => $d['vigencia_hasta'] ?? null];

        return $d['tipo'] === 'VACACIONES'
            ? $base + ['id_modelo_vacaciones' => $d['id_modelo'], 'fuente' => $d['fuente'] ?? null]
            : $base + ['id_modelo_antiguedad' => $d['id_modelo'], 'estado_validacion' => $d['estado_validacion'] ?? 'SIN_CONFIRMAR'];
    }

    /** @return class-string */
    private function modelo(string $tipo): string
    {
        return $tipo === 'VACACIONES' ? MotorConvenioModeloVacaciones::class : MotorConvenioModeloAntiguedad::class;
    }

    private function catalogos(): array
    {
        return [
            'convenios' => array_map(fn ($c) => ['value' => $c->id, 'label' => "{$c->codigo} — {$c->descripcion}"], $this->convenios->todos()),
            'modelos_antiguedad' => MotorModeloAntiguedad::query()->where('activo', 1)->get()->map(fn ($m) => ['value' => $m->id_modelo_antiguedad, 'label' => "{$m->codigo} — {$m->descripcion}"])->all(),
            'modelos_vacaciones' => MotorModeloVacaciones::query()->where('activo', 1)->get()->map(fn ($m) => ['value' => $m->id_modelo_vacaciones, 'label' => "{$m->codigo} — {$m->descripcion}"])->all(),
        ];
    }
}
