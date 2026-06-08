<?php

namespace App\Http\Controllers;

use App\Models\Sue006;       // Categorías
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriasController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'Categorias';
    private const RUTA = 'categorias';

    public function index($id = null, $direction = null)
    {
        if ($id == null || $id == 0) {
            $registro = Sue006::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue006::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue006::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue006::latest()->first() ?? Sue006::orderBy('codigo')->first();
        } else {
            $registro = Sue006::find($id) ?? Sue006::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue006,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue006(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue006::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue006::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue006::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue006::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue006::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue006::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue006::orderBy('codigo')->first() ?? new Sue006,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue006 $registro)
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

    public function update(Request $request, Sue006 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Sue006 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    public function first()
    {
        $registro = Sue006::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue006::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue006::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue006::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue006::query();

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
        $unique = 'unique:sue006s,codigo' . ($id ? ',' . $id : '');

        return $request->validate([
            'codigo'    => 'required|string|max:4|' . $unique,
            'detalle'   => 'required|string|max:100',
            'sue_bas'   => 'nullable|integer|min:0',
            'hsnormal'  => 'nullable|integer|min:0',
            'hsmin'     => 'nullable|integer|min:0',
            'hsmax'     => 'nullable|integer|min:0',
            'cod_conve' => 'nullable|string|max:5',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe una categoría con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
        ]);
    }
}
