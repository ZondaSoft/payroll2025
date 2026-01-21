<?php

namespace App\Http\Controllers;

use App\Models\Sicoss05;    // Condiciones sicoss
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class SicossCondicController extends Controller
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
            $legajo = Sicoss05::orderBy('codigo')
                ->first();      // find($id);
        } else {
            //------------------------------
            //      Primer registro
            //------------------------------
            if ($direction == null) {
                $legajo = Sicoss05::find($id);
                if (!$legajo) {
                    $legajo = Sicoss05::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss05;
                }
            } elseif ($direction == -1) {
                //------------------------------
                //      Registro anterior
                //------------------------------
                $legajo = Sicoss05::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($legajo == null) {
                    $legajo = Sicoss05::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss05;
                }
            } elseif ($direction == 1) {
                //------------------------------
                //      Registro siguiente
                //------------------------------
                $legajo = Sicoss05::find($id);
                if ($legajo == null) {
                    $legajo = Sicoss05::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss05;
                }

            } elseif ($direction == -9) {
                //------------------------------
                //      Ultimo registro
                //------------------------------
                $legajo = Sicoss05::latest()->first();

                if ($legajo == null) {
                    $legajo = Sicoss05::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss05;
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
            $legajo = new Sicoss05;

        // formulario vue via inertia
        return Inertia::render('Sicoss/Condiciones', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'empresa' => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render('Sicoss/Condiciones', [
            'legajo' => new Sicoss05(),   // id null, codigo null, etc
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
            'codigo' => 'required|integer|min:0|max:999999|unique:Sicoss05s,codigo',
            'detalle' => 'required|string|max:100',
        ]);

        Sicoss05::create($validated);

        return redirect()->route('sicoss.condiciones.index')
            ->with('success', 'Condición creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $legajo = Sicoss05::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $legajo = Sicoss05::find($id);
            } elseif ($direction == -1) {
                $legajo = Sicoss05::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $legajo = Sicoss05::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $legajo = Sicoss05::orderBy('id', 'desc')->first();
            }

            if (!$legajo) {
                $legajo = Sicoss05::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Sicoss/Condiciones', [
            'legajo' => $legajo ?? new Sicoss05,
            'agregar' => false,
            'edicion' => false,
            'active' => 64,
            'empresa' => $empresa,
        ]);
    }

    public function edit(Sicoss05 $actividad)
    {
        if (!$actividad->exists) {
            return redirect()
                ->route('sicoss.condiciones.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('Sicoss/Condiciones', [
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
    public function update(Request $request, Sicoss05 $actividad)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:100',
        ]);

        $actividad->update($validated);

        return redirect()->route('sicoss.condiciones.index')
            ->with('success', 'Condición actualizada exitosamente.');
    }

    /**
     * Elimina un actividad de la base de datos
     */
    public function destroy(Sicoss05 $actividad)
    {
        $actividad->delete();

        return redirect()->route('sicoss.condiciones.index')
            ->with('success', 'Condición eliminada exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $legajo = Sicoss05::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.condiciones.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.condiciones.show', $legajo->id);
    }

    // Último registro
    public function last()
    {
        $legajo = Sicoss05::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.condiciones.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.condiciones.show', $legajo->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = Sicoss05::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('sicoss.condiciones.index', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('sicoss.condiciones.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = Sicoss05::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('sicoss.condiciones.index', $nextId)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('sicoss.condiciones.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = Sicoss05::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('apellido', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('legajo_numero', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Sicoss/Condiciones/Search', [
            'condiciones' => $query->paginate(20),
            'filters' => $request->only('search')
        ]);
    }
}
