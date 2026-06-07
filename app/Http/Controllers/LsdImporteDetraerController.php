<?php

namespace App\Http\Controllers;

use App\Models\LsdImporteDetraer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LsdImporteDetraerController extends Controller
{
    public function index()
    {
        $importes = LsdImporteDetraer::with('usuario:id,name')
            ->orderByDesc('periodo_desde')
            ->paginate(20);

        return Inertia::render('Sicoss/ImportesDetraer/Index', [
            'importes' => $importes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Sicoss/ImportesDetraer/Form', [
            'importe' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['usuario_id'] = auth()->id();

        LsdImporteDetraer::create($data);

        return redirect()->route('sicoss.importes-detraer.index')
            ->with('success', 'Importe a detraer creado correctamente.');
    }

    public function edit($id)
    {
        $importe = LsdImporteDetraer::findOrFail($id);

        return Inertia::render('Sicoss/ImportesDetraer/Form', [
            'importe' => $importe,
        ]);
    }

    public function update(Request $request, $id)
    {
        $importe = LsdImporteDetraer::findOrFail($id);
        $data = $this->validar($request, $importe->id);

        $importe->update($data);

        return redirect()->route('sicoss.importes-detraer.index')
            ->with('success', 'Importe a detraer actualizado correctamente.');
    }

    public function destroy($id)
    {
        $importe = LsdImporteDetraer::findOrFail($id);
        $importe->delete();

        return redirect()->route('sicoss.importes-detraer.index')
            ->with('success', 'Importe a detraer eliminado correctamente.');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        $rules = [
            'periodo_desde' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
                'unique:lsd_importes_detraer,periodo_desde' . ($ignorarId ? ",{$ignorarId}" : ''),
            ],
            'importe' => 'required|numeric|min:0',
            'normativa' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ];

        $messages = [
            'periodo_desde.required' => 'El período es obligatorio.',
            'periodo_desde.size' => 'El período debe tener formato AAAAMM (6 dígitos).',
            'periodo_desde.regex' => 'El período debe ser numérico con formato AAAAMM.',
            'periodo_desde.unique' => 'Ya existe un importe registrado para ese período.',
            'importe.required' => 'El importe es obligatorio.',
            'importe.numeric' => 'El importe debe ser un número.',
            'importe.min' => 'El importe no puede ser negativo.',
            'normativa.max' => 'La normativa no puede superar los 255 caracteres.',
        ];

        return $request->validate($rules, $messages);
    }
}
