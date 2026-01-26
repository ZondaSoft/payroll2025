<?php

namespace App\Http\Controllers;

use App\Models\SicossZona;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class SicossLocalidadesController extends Controller
{
    public function index($id = null, $direction = null)
    {
        $legajo = null;
        $agregar = False;
        $edicion = False;    // True: Muestra botones Grabar - Cancelar   //  False: Muestra botones: Agregar, Editar, Borrar
        $active = 64;
        //$now = Carbon::now();
        $alta = '';
        $rol = '';

        // Obtener el usuario actual autenticado
        $user = auth()->user();

        // Recupero roles del usuario
        $roles = $user->getRoleNames(); // Retorna una colección de roles
        if ($roles) {
            $rol = $roles->first();
        }

        // Busco registro
        if ($id == null or $id == 0) {
            //------------------------------
            //      Primer registro
            //------------------------------
            $legajo = SicossZona::orderBy('codigo')
                ->first();      // find($id);
        } else {
            //------------------------------
            //      Primer registro
            //------------------------------
            if ($direction == null) {
                $legajo = SicossZona::find($id);
                if (!$legajo) {
                    $legajo = SicossZona::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossZona;
                }
            } elseif ($direction == -1) {
                //------------------------------
                //      Registro anterior
                //------------------------------
                $legajo = SicossZona::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($legajo == null) {
                    $legajo = SicossZona::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossZona;
                }
            } elseif ($direction == 1) {
                //------------------------------
                //      Registro siguiente
                //------------------------------
                $legajo = SicossZona::find($id);
                if ($legajo == null) {
                    $legajo = SicossZona::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossZona;
                }

            } elseif ($direction == -9) {
                //------------------------------
                //      Ultimo registro
                //------------------------------
                $legajo = SicossZona::latest()->first();

                if ($legajo == null) {
                    $legajo = SicossZona::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossZona;
                }
            }
        }

        // Datos de la empresa
        $empresa = Datoempr::first();      // find($id);
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        // Si a pesar de todos los controles $legajo es null es porque no hay registros
        if ($legajo == null)
            $legajo = new SicossZona;

        // formulario vue via inertia
        return Inertia::render('Sicoss/Localidades', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'empresa' => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render('Sicoss/Localidades', [
            'legajo' => new SicossZona(),   // id null, codigo null, etc
            'agregar' => true,
            'edicion' => true,
            'active' => 64,
            'empresa' => Datoempr::first(),
        ]);
    }

    /**
     * Almacena un nuevo actividad en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|integer|min:0|max:999999|unique:sicoss01s,codigo',
            'detalle' => 'required|string|max:100',
        ]);

        SicossZona::create($validated);

        return redirect()->route('sicoss.localidades.index')
            ->with('success', 'Actividad creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $legajo = SicossZona::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $legajo = SicossZona::find($id);
            } elseif ($direction == -1) {
                $legajo = SicossZona::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $legajo = SicossZona::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $legajo = SicossZona::orderBy('id', 'desc')->first();
            }

            if (!$legajo) {
                $legajo = SicossZona::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Sicoss/Localidades', [
            'legajo' => $legajo ?? new SicossZona,
            'agregar' => false,
            'edicion' => false,
            'active' => 64,
            'empresa' => $empresa,
        ]);
    }

    public function edit(SicossZona $actividad)
    {
        if (!$actividad->exists) {
            return redirect()
                ->route('sicoss.localidades.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('Sicoss/Localidades', [
            'legajo' => $actividad,
            'agregar' => false,
            'edicion' => true,
            'active' => 64,
            'empresa' => $empresa,
        ]);
    }

    /**
     * Actualiza un actividad en la base de datos
     */
    public function update(Request $request, SicossZona $actividad)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:100',
        ]);

        $actividad->update($validated);

        return redirect()->route('sicoss.localidades.index')
            ->with('success', 'Actividad actualizada exitosamente.');
    }

    /**
     * Elimina un actividad de la base de datos
     */
    public function destroy(SicossZona $actividad)
    {
        $actividad->delete();

        return redirect()->route('sicoss.localidades.index')
            ->with('success', 'Actividad eliminada exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $legajo = SicossZona::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.localidades.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.localidades.show', $legajo->id);
    }

    // Último registro
    public function last()
    {
        $legajo = SicossZona::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.localidades.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.localidades.show', $legajo->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = SicossZona::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('sicoss.localidades.index', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('sicoss.localidades.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = SicossZona::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('sicoss.localidades.index', $nextId)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('sicoss.localidades.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = SicossZona::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('apellido', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('legajo_numero', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Sicoss/Localidades/Search', [
            'localidades' => $query->paginate(20),
            'filters' => $request->only('search')
        ]);
    }
}
