<?php

namespace App\Http\Controllers;

use App\Models\Sue030;       // Centros de costo
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CentrosCostoController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'CentrosCosto';
    private const RUTA = 'centros.costo';

    public function index($id = null, $direction = null)
    {
        $registro = null;

        if ($id == null || $id == 0) {
            $registro = Sue030::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue030::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue030::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue030::latest()->first() ?? Sue030::orderBy('codigo')->first();
        } else {
            $registro = Sue030::find($id) ?? Sue030::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue030,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue030(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue030::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Centro de costo creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue030::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue030::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue030::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue030::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue030::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue030::orderBy('codigo')->first() ?? new Sue030,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue030 $registro)
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

    public function update(Request $request, Sue030 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Centro de costo actualizado exitosamente.');
    }

    public function destroy(Sue030 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Centro de costo eliminado exitosamente.');
    }

    public function first()
    {
        $registro = Sue030::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue030::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue030::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue030::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue030::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('detalle', 'LIKE', "%{$search}%")
                  ->orWhere('responsa', 'LIKE', "%{$search}%");
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
        $unique = 'unique:sue030s,codigo' . ($id ? ',' . $id : '');

        return $request->validate([
            'codigo'    => 'required|string|max:4|' . $unique,
            'detalle'   => 'required|string|max:35',
            'responsa'  => 'nullable|string|max:35',
            'domicilio' => 'nullable|string|max:35',
            'localidad' => 'nullable|string|max:30',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe un centro de costo con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
        ]);
    }
}
