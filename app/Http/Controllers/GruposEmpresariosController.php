<?php

namespace App\Http\Controllers;

use App\Models\Sue086;       // Grupos empresarios
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GruposEmpresariosController extends Controller
{
    private const ACTIVE = 80;

    public function index($id = null, $direction = null)
    {
        $legajo = null;
        $agregar = false;
        $edicion = false;

        if ($id == null || $id == 0) {
            $legajo = Sue086::orderBy('codigo')->first();
        } else {
            if ($direction == null) {
                $legajo = Sue086::find($id);
                if (!$legajo) {
                    $legajo = Sue086::orderBy('codigo')->first();
                }
            } elseif ($direction == -1) {
                $legajo = Sue086::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($legajo == null) {
                    $legajo = Sue086::orderBy('codigo')->first();
                }
            } elseif ($direction == 1) {
                $legajo = Sue086::find($id);
                if ($legajo == null) {
                    $legajo = Sue086::orderBy('codigo')->first();
                }
            } elseif ($direction == -9) {
                $legajo = Sue086::latest()->first();
                if ($legajo == null) {
                    $legajo = Sue086::orderBy('codigo')->first();
                }
            }
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        if ($legajo == null) {
            $legajo = new Sue086;
        }

        return Inertia::render('GruposEmpresarios/Index', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => self::ACTIVE,
            'empresa' => $empresa,
            'tiposEmpleadorLsd' => Sue086::TIPOS_EMPLEADOR_LSD,
        ]);
    }

    public function create()
    {
        return Inertia::render('GruposEmpresarios/Index', [
            'legajo' => new Sue086(),
            'agregar' => true,
            'edicion' => true,
            'active' => self::ACTIVE,
            'empresa' => Datoempr::first(),
            'tiposEmpleadorLsd' => Sue086::TIPOS_EMPLEADOR_LSD,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:2|unique:sue086s,codigo',
            'detalle' => 'required|string|max:40',
            'fantasia' => 'nullable|string|max:100',
            'cuit' => 'nullable|string|max:100',
            'direccion_comercial' => 'nullable|string|max:100',
            'localidad_comercial' => 'nullable|string|max:100',
            'cod_pos_comercial' => 'nullable|string|max:100',
            'direccion_fiscal' => 'nullable|string|max:100',
            'localidad_fiscal' => 'nullable|string|max:100',
            'cod_pos_fiscal' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'web' => 'nullable|string|max:100',
            'tipo' => 'nullable|string|max:3',
            'actividad' => 'nullable|string|max:150',
            'tipo_empleador_lsd' => 'required|string|in:0,1,2,4,5,7,8',
            'nom_arch' => 'nullable|string|max:255',
            'legajo_desde' => 'nullable|integer|min:0',
            'legajo_hasta' => 'nullable|integer|min:0',
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no puede tener más de 2 caracteres.',
            'codigo.unique' => 'Ya existe un grupo empresario con ese código.',
            'detalle.required' => 'El detalle es obligatorio.',
            'detalle.max' => 'El detalle no puede tener más de 40 caracteres.',
            'email.email' => 'El email no tiene un formato válido.',
        ]);

        Sue086::create($validated);

        return redirect()->route('grupos.empresarios.index')
            ->with('success', 'Grupo empresario creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $legajo = Sue086::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $legajo = Sue086::find($id);
            } elseif ($direction == -1) {
                $legajo = Sue086::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $legajo = Sue086::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $legajo = Sue086::orderBy('id', 'desc')->first();
            }

            if (!$legajo) {
                $legajo = Sue086::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('GruposEmpresarios/Index', [
            'legajo' => $legajo ?? new Sue086,
            'agregar' => false,
            'edicion' => false,
            'active' => self::ACTIVE,
            'empresa' => $empresa,
            'tiposEmpleadorLsd' => Sue086::TIPOS_EMPLEADOR_LSD,
        ]);
    }

    public function edit(Sue086 $grupo)
    {
        if (!$grupo->exists) {
            return redirect()
                ->route('grupos.empresarios.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('GruposEmpresarios/Index', [
            'legajo' => $grupo,
            'agregar' => false,
            'edicion' => true,
            'active' => self::ACTIVE,
            'empresa' => $empresa,
            'tiposEmpleadorLsd' => Sue086::TIPOS_EMPLEADOR_LSD,
        ]);
    }

    public function update(Request $request, Sue086 $grupo)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:40',
            'fantasia' => 'nullable|string|max:100',
            'cuit' => 'nullable|string|max:100',
            'direccion_comercial' => 'nullable|string|max:100',
            'localidad_comercial' => 'nullable|string|max:100',
            'cod_pos_comercial' => 'nullable|string|max:100',
            'direccion_fiscal' => 'nullable|string|max:100',
            'localidad_fiscal' => 'nullable|string|max:100',
            'cod_pos_fiscal' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'web' => 'nullable|string|max:100',
            'tipo' => 'nullable|string|max:3',
            'actividad' => 'nullable|string|max:150',
            'tipo_empleador_lsd' => 'required|string|in:0,1,2,4,5,7,8',
            'nom_arch' => 'nullable|string|max:255',
            'legajo_desde' => 'nullable|integer|min:0',
            'legajo_hasta' => 'nullable|integer|min:0',
        ], [
            'detalle.required' => 'El detalle es obligatorio.',
            'detalle.max' => 'El detalle no puede tener más de 40 caracteres.',
            'email.email' => 'El email no tiene un formato válido.',
        ]);

        $grupo->update($validated);

        return redirect()->route('grupos.empresarios.index')
            ->with('success', 'Grupo empresario actualizado exitosamente.');
    }

    public function destroy(Sue086 $grupo)
    {
        $grupo->delete();

        return redirect()->route('grupos.empresarios.index')
            ->with('success', 'Grupo empresario eliminado exitosamente.');
    }

    public function first()
    {
        $legajo = Sue086::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('grupos.empresarios.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('grupos.empresarios.show', $legajo->id);
    }

    public function last()
    {
        $legajo = Sue086::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('grupos.empresarios.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('grupos.empresarios.show', $legajo->id);
    }

    public function previous($id)
    {
        $previousId = Sue086::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('grupos.empresarios.show', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('grupos.empresarios.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Sue086::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('grupos.empresarios.show', $id)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('grupos.empresarios.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Sue086::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('detalle', 'LIKE', "%{$search}%")
                  ->orWhere('fantasia', 'LIKE', "%{$search}%")
                  ->orWhere('cuit', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('GruposEmpresarios/Search', [
            'grupos' => $query->orderBy('codigo')->paginate(20),
            'filters' => $request->only('search'),
            'active' => self::ACTIVE,
        ]);
    }
}
