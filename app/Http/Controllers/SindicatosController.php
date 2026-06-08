<?php

namespace App\Http\Controllers;

use App\Models\Sue015;       // Sindicatos
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SindicatosController extends Controller
{
    private const ACTIVE = 80;
    private const VIEW = 'Sindicatos';
    private const RUTA = 'sindicatos';

    public function index($id = null, $direction = null)
    {
        if ($id == null || $id == 0) {
            $registro = Sue015::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue015::where('id', '<', $id)->orderBy('id', 'desc')->first() ?? Sue015::orderBy('codigo')->first();
        } elseif ($direction == -9) {
            $registro = Sue015::latest()->first() ?? Sue015::orderBy('codigo')->first();
        } else {
            $registro = Sue015::find($id) ?? Sue015::orderBy('codigo')->first();
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? new Sue015,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render(self::VIEW . '/Index', [
            'registro' => new Sue015(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        Sue015::create($this->validar($request));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sindicato creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $registro = Sue015::orderBy('codigo')->first();
        } elseif ($direction == -1) {
            $registro = Sue015::where('id', '<', $id)->orderBy('id', 'desc')->first();
        } elseif ($direction == 1) {
            $registro = Sue015::where('id', '>', $id)->orderBy('id', 'asc')->first();
        } elseif ($direction == -9) {
            $registro = Sue015::orderBy('id', 'desc')->first();
        } else {
            $registro = Sue015::find($id);
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render(self::VIEW . '/Index', [
            'registro' => $registro ?? Sue015::orderBy('codigo')->first() ?? new Sue015,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue015 $registro)
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

    public function update(Request $request, Sue015 $registro)
    {
        $registro->update($this->validar($request, $registro->id));

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sindicato actualizado exitosamente.');
    }

    public function destroy(Sue015 $registro)
    {
        $registro->delete();

        return redirect()->route(self::RUTA . '.index')
            ->with('success', 'Sindicato eliminado exitosamente.');
    }

    public function first()
    {
        $registro = Sue015::orderBy('id', 'asc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function last()
    {
        $registro = Sue015::orderBy('id', 'desc')->first();
        if (!$registro) return redirect()->route(self::RUTA . '.index')->with('error', 'No hay registros');
        return redirect()->route(self::RUTA . '.show', $registro->id);
    }

    public function previous($id)
    {
        $previousId = Sue015::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;
        if (!$previousId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro anterior');
        return redirect()->route(self::RUTA . '.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue015::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;
        if (!$nextId) return redirect()->route(self::RUTA . '.index')->with('warning', 'No hay registro siguiente');
        return redirect()->route(self::RUTA . '.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue015::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('detalle', 'LIKE', "%{$search}%")
                  ->orWhere('localidad', 'LIKE', "%{$search}%");
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
        $unique = 'unique:sue015s,codigo' . ($id ? ',' . $id : '');

        return $request->validate([
            'codigo'    => 'required|string|max:2|' . $unique,
            'detalle'   => 'required|string|max:30',
            'localidad' => 'nullable|string|max:25',
            'cp'        => 'nullable|string|max:10',
            'tel1'      => 'nullable|string|max:20',
            'tel2'      => 'nullable|string|max:20',
            'tel3'      => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:45',
            'web'       => 'nullable|string|max:45',
            'contacto'  => 'nullable|string|max:45',
            'porce_con' => 'nullable|numeric|min:0',
            'porce_apo' => 'nullable|numeric|min:0',
            'fijo_apo'  => 'nullable|numeric|min:0',
            'fijo_con'  => 'nullable|numeric|min:0',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe un sindicato con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
            'email.email'      => 'El email no tiene un formato válido.',
        ]);
    }
}
