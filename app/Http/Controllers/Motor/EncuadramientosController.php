<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorEmpresaEncuadramiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Línea de tiempo del encuadramiento patronal A/B por empresa (legacy sue086s). */
class EncuadramientosController extends Controller
{
    public function __construct(private readonly ProveedorEmpresas $empresas) {}

    public function index(): Response
    {
        $emp = [];
        foreach ($this->empresas->todas() as $e) {
            $emp[$e->id] = "{$e->codigo} — {$e->razonSocial}";
        }
        $hoy = now()->format('Y-m-d');
        $items = MotorEmpresaEncuadramiento::query()->orderBy('empresa_id')->orderByDesc('vigencia_desde')->get()->map(fn ($x) => [
            'id' => $x->encuadramiento_id, 'empresa_id' => $x->empresa_id, 'empresa' => $emp[$x->empresa_id] ?? "#{$x->empresa_id}", 'encuadramiento' => $x->encuadramiento, 'detalle' => $x->detalle, 'desde' => $x->vigencia_desde, 'hasta' => $x->vigencia_hasta,
            'certificado' => $x->certificado_mipyme, 'vencimiento' => $x->vencimiento_certificado, 'estado' => $x->estado_validacion, 'vigente' => $x->vigencia_desde <= $hoy && ($x->vigencia_hasta === null || $x->vigencia_hasta >= $hoy),
        ])->all();
        $sinEncuadramiento = array_values(array_filter($this->empresas->todas(), fn ($e) => ! collect($items)->contains(fn ($i) => $i['empresa_id'] === $e->id && $i['vigente'])));

        return Inertia::render('Motor/Encuadramientos/Index', ['items' => $items, 'sin_encuadramiento' => array_map(fn ($e) => ['id' => $e->id, 'label' => "{$e->codigo} — {$e->razonSocial}"], $sinEncuadramiento), 'empresas' => $this->opciones()]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Encuadramientos/Form', ['item' => null, 'empresas' => $this->opciones()]);
    }

    public function edit(int $encuadramiento): Response
    {
        return Inertia::render('Motor/Encuadramientos/Form', ['item' => MotorEmpresaEncuadramiento::query()->findOrFail($encuadramiento), 'empresas' => $this->opciones()]);
    }

    public function store(Request $request): RedirectResponse
    {
        MotorEmpresaEncuadramiento::query()->create($this->validar($request, null) + ['usuario_id' => auth()->id()]);

        return redirect()->route('motor.encuadramientos.index')->with('success', 'Encuadramiento registrado.');
    }

    public function update(Request $request, int $encuadramiento): RedirectResponse
    {
        $m = MotorEmpresaEncuadramiento::query()->findOrFail($encuadramiento);
        $m->update($this->validar($request, $m));

        return redirect()->route('motor.encuadramientos.index')->with('success', 'Encuadramiento actualizado.');
    }

    public function destroy(int $encuadramiento): RedirectResponse
    {
        MotorEmpresaEncuadramiento::query()->whereKey($encuadramiento)->delete();

        return redirect()->route('motor.encuadramientos.index')->with('success', 'Encuadramiento eliminado.');
    }

    private function validar(Request $request, ?MotorEmpresaEncuadramiento $actual): array
    {
        $d = $request->validate([
            'empresa_id' => 'required|integer', 'encuadramiento' => 'required|in:A,B', 'detalle' => 'nullable|string|max:100', 'vigencia_desde' => 'required|date_format:Y-m-d', 'vigencia_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:vigencia_desde',
            'certificado_mipyme' => 'nullable|string|max:100', 'vencimiento_certificado' => 'nullable|date_format:Y-m-d', 'origen' => 'nullable|string|max:100', 'documento_respaldo' => 'nullable|string|max:100', 'estado_validacion' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
        ], ['empresa_id.required' => 'Elegí la empresa.', 'encuadramiento.required' => 'Indicá A o B.', 'vigencia_hasta.after_or_equal' => 'Vigencia hasta no puede ser anterior a vigencia desde.']);
        if ($this->empresas->porId((int) $d['empresa_id']) === null) {
            abort(422, 'La empresa no existe en sue086s.');
        }
        $q = MotorEmpresaEncuadramiento::query()->where('empresa_id', $d['empresa_id'])->where('vigencia_desde', '<=', $d['vigencia_hasta'] ?? '9999-12-31')->where(fn ($w) => $w->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $d['vigencia_desde']));
        if ($actual !== null) {
            $q->whereKeyNot($actual->encuadramiento_id);
        }
        if ($q->exists()) {
            abort(422, 'RT-007: la vigencia se superpone con otro encuadramiento de la misma empresa. Es una línea de tiempo: cerrá el anterior.');
        }

        return $d;
    }

    private function opciones(): array
    {
        return array_map(fn ($e) => ['value' => $e->id, 'label' => "{$e->codigo} — {$e->razonSocial}"], $this->empresas->todas());
    }
}
