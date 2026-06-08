<?php

namespace App\Http\Controllers;

use App\Models\Sue054;       // Cuadrillas
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CuadrillasController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'Cuadrillas';
    private const RUTA = 'cuadrillas';

    public function index($id = null, $direction = null)
    {
        if ($id == null || $id == 0) {
            $registro = Sue054::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue054::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue054::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue054::latest()->first() ?? Sue054::orderBy('codigo')->first();
        } else {
            $registro = Sue054::find($id) ?? Sue054::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue054,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue054(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue054::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Cuadrilla creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue054::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue054::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue054::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue054::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue054::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue054::orderBy('codigo')->first() ?? new Sue054,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue054 $registro)
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

    public function update(Request $request, Sue054 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Cuadrilla actualizada exitosamente.');
    }

    public function destroy(Sue054 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Cuadrilla eliminada exitosamente.');
    }

    public function first()
    {
        $registro = Sue054::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue054::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue054::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue054::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue054::query();

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
        $unique = 'unique:sue054s,codigo' . ($id ? ',' . $id : '');

        return $request->validate([
            'codigo'    => 'required|string|max:4|' . $unique,
            'detalle'   => 'required|string|max:35',
            'encargado' => 'nullable|integer|min:0',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe una cuadrilla con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
        ]);
    }
}
