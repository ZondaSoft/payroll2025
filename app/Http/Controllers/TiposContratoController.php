<?php

namespace App\Http\Controllers;

use App\Models\Sue107;       // Tipos de contrato
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposContratoController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'TiposContrato';
    private const RUTA = 'tipos.contrato';

    public function index($id = null, $direction = null)
    {
        if ($id == null || $id == 0) {
            $registro = Sue107::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue107::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue107::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue107::latest()->first() ?? Sue107::orderBy('codigo')->first();
        } else {
            $registro = Sue107::find($id) ?? Sue107::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue107,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue107(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue107::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Tipo de contrato creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue107::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue107::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue107::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue107::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue107::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue107::orderBy('codigo')->first() ?? new Sue107,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue107 $registro)
    {
        if (!$registro->exists) {
            return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro,
            'agregar'  => false,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function update(Request $request, Sue107 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Tipo de contrato actualizado exitosamente.');
    }

    public function destroy(Sue107 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Tipo de contrato eliminado exitosamente.');
    }

    public function first()
    {
        $registro = Sue107::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue107::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue107::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue107::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue107::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('detalle', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render(self::VIEW . '/Search', [
            'registros' => $query->orderBy('codigo')->paginate(20),
            'filters'   => $request->only('search'),
            'active'    => self::ACTIVE,
        ]);
    }

    private function validar(Request $request, ?int $id = null): array
    {
        $unique = 'unique:sue107s,codigo' . ($id ? ',' . $id : '');

        return $request->validate([
            'codigo'   => 'required|integer|min:0|' . $unique,
            'detalle'  => 'required|string|max:40',
            'duracion' => 'nullable|integer|min:0',
            'aviso'    => 'nullable|integer|min:0',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.integer'   => 'El código debe ser numérico.',
            'codigo.unique'    => 'Ya existe un tipo de contrato con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
        ]);
    }
}
