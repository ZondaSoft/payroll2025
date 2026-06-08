<?php

namespace App\Http\Controllers;

use App\Models\Sue011;       // Sectores
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectoresController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'Sectores';
    private const RUTA = 'sectores';

    public function index($id = null, $direction = null)
    {
        if ($id == null || $id == 0) {
            $registro = Sue011::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue011::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue011::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue011::latest()->first() ?? Sue011::orderBy('codigo')->first();
        } else {
            $registro = Sue011::find($id) ?? Sue011::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue011,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue011(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue011::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sector creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue011::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue011::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue011::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue011::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue011::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue011::orderBy('codigo')->first() ?? new Sue011,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue011 $registro)
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

    public function update(Request $request, Sue011 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sector actualizado exitosamente.');
    }

    public function destroy(Sue011 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sector eliminado exitosamente.');
    }

    public function first()
    {
        $registro = Sue011::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue011::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue011::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue011::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue011::query();

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
        $unique = 'unique:sue011s,codigo' . ($id ? ',' . $id : '');

        $validated = $request->validate([
            'codigo'                => 'required|string|max:3|' . $unique,
            'detalle'               => 'required|string|max:30',
            'color'                 => 'nullable|string|max:255',
            'vacac_tipo_dias'       => 'nullable|string|in:habiles,corridos',
            'vacac_max_simultaneos' => 'nullable|integer|min:0',
            'tipo_horar'            => 'nullable|integer|min:0',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe un sector con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
        ]);

        // Columnas NOT NULL sin default en la tabla.
        $validated['color'] = $validated['color'] ?? '0';
        $validated['vacac_tipo_dias'] = $validated['vacac_tipo_dias'] ?? 'habiles';

        return $validated;
    }
}
