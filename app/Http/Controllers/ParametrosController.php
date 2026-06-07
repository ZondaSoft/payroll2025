<?php

namespace App\Http\Controllers;

use App\Models\Sue089;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParametrosController extends Controller
{
    public function index()
    {
        $parametros = Sue089::orderBy('desde')->paginate(20);

        return Inertia::render('Config/Parametros/Index', [
            'parametros' => $parametros,
        ]);
    }

    public function create()
    {
        return Inertia::render('Config/Parametros/Form', [
            'parametro' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        Sue089::create($data);

        return redirect()->route('config.parametros.index')
            ->with('success', 'Parámetro creado correctamente.');
    }

    public function edit($id)
    {
        $parametro = Sue089::findOrFail($id);

        return Inertia::render('Config/Parametros/Form', [
            'parametro' => $parametro,
        ]);
    }

    public function update(Request $request, $id)
    {
        $parametro = Sue089::findOrFail($id);
        $data = $this->validar($request);

        $parametro->update($data);

        return redirect()->route('config.parametros.index')
            ->with('success', 'Parámetro actualizado correctamente.');
    }

    public function destroy($id)
    {
        $parametro = Sue089::findOrFail($id);
        $parametro->delete();

        return redirect()->route('config.parametros.index')
            ->with('success', 'Parámetro eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        $rules = [
            'desde'   => 'required|integer|min:0',
            'hasta'   => 'required|integer|gte:desde',
            'tiporem' => 'required|string|in:H,D,A,NR,GA,DG,RE,AP,AU',
        ];

        $messages = [
            'desde.required'   => 'El campo "Desde" es obligatorio.',
            'desde.integer'    => 'El campo "Desde" debe ser un número entero.',
            'desde.min'        => 'El campo "Desde" no puede ser negativo.',
            'hasta.required'   => 'El campo "Hasta" es obligatorio.',
            'hasta.integer'    => 'El campo "Hasta" debe ser un número entero.',
            'hasta.gte'        => 'El campo "Hasta" debe ser mayor o igual que "Desde".',
            'tiporem.required' => 'El tipo es obligatorio.',
            'tiporem.in'       => 'El tipo seleccionado no es válido.',
        ];

        return $request->validate($rules, $messages);
    }
}
