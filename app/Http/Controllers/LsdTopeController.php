<?php

namespace App\Http\Controllers;

use App\Models\LsdTope;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LsdTopeController extends Controller
{
    public function index()
    {
        $topes = LsdTope::with('usuario:id,name')
            ->orderByDesc('periodo_desde')
            ->paginate(20);

        return Inertia::render('Sicoss/Topes/Index', [
            'topes' => $topes,
        ]);
    }

    public function create()
    {
        // Sugerir por defecto el último período cargado + 1 mes (o el período actual si no hay ninguno).
        $ultimo = LsdTope::max('periodo_desde');
        $periodoSugerido = $ultimo ? $this->siguientePeriodo($ultimo) : now()->format('Ym');

        return Inertia::render('Sicoss/Topes/Form', [
            'tope' => null,
            'periodoSugerido' => $periodoSugerido,
        ]);
    }

    /**
     * Dado un período YYYYMM, devuelve el siguiente (maneja el cambio de año: 202612 → 202701).
     */
    private function siguientePeriodo(string $yyyymm): string
    {
        $anio = (int) substr($yyyymm, 0, 4);
        $mes = (int) substr($yyyymm, 4, 2);
        if (++$mes > 12) {
            $mes = 1;
            $anio++;
        }
        return sprintf('%04d%02d', $anio, $mes);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['usuario_id'] = auth()->id();

        LsdTope::create($data);

        return redirect()->route('sicoss.topes.index')
            ->with('success', 'Tope creado correctamente.');
    }

    public function edit($id)
    {
        $tope = LsdTope::findOrFail($id);

        return Inertia::render('Sicoss/Topes/Form', [
            'tope' => $tope,
        ]);
    }

    public function update(Request $request, $id)
    {
        $tope = LsdTope::findOrFail($id);
        $data = $this->validar($request, $tope->id);

        $tope->update($data);

        return redirect()->route('sicoss.topes.index')
            ->with('success', 'Tope actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tope = LsdTope::findOrFail($id);
        $tope->delete();

        return redirect()->route('sicoss.topes.index')
            ->with('success', 'Tope eliminado correctamente.');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        $rules = [
            'periodo_desde' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
                'unique:lsd_topes,periodo_desde' . ($ignorarId ? ",{$ignorarId}" : ''),
            ],
            'tope_aportes' => 'required|numeric|min:0',
            'base_minima' => 'nullable|numeric|min:0',
            'ipc_porcentaje' => 'nullable|numeric',
            'normativa' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ];

        $messages = [
            'periodo_desde.required' => 'El período es obligatorio.',
            'periodo_desde.size' => 'El período debe tener formato AAAAMM (6 dígitos).',
            'periodo_desde.regex' => 'El período debe ser numérico con formato AAAAMM.',
            'periodo_desde.unique' => 'Ya existe un tope registrado para ese período.',
            'tope_aportes.required' => 'La base máxima (tope) es obligatoria.',
            'tope_aportes.numeric' => 'La base máxima debe ser un número.',
            'tope_aportes.min' => 'La base máxima no puede ser negativa.',
            'base_minima.numeric' => 'La base mínima debe ser un número.',
            'ipc_porcentaje.numeric' => 'El % IPC debe ser un número.',
            'normativa.max' => 'La resolución no puede superar los 255 caracteres.',
        ];

        return $request->validate($rules, $messages);
    }
}
