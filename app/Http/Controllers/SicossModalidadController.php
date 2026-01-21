<?php

namespace App\Http\Controllers;

use App\Models\Sicoss08;    // Modalidades sicoss
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class SicossModalidadController extends Controller
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
            $legajo = Sicoss08::orderBy('codigo')
                ->first();      // find($id);
        } else {
            //------------------------------
            //      Primer registro
            //------------------------------
            if ($direction == null) {
                $legajo = Sicoss08::find($id);
                if (!$legajo) {
                    $legajo = Sicoss08::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss08;
                }
            } elseif ($direction == -1) {
                //------------------------------
                //      Registro anterior
                //------------------------------
                $legajo = Sicoss08::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($legajo == null) {
                    $legajo = Sicoss08::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss08;
                }
            } elseif ($direction == 1) {
                //------------------------------
                //      Registro siguiente
                //------------------------------
                $legajo = Sicoss08::find($id);
                if ($legajo == null) {
                    $legajo = Sicoss08::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss08;
                }

            } elseif ($direction == -9) {
                //------------------------------
                //      Ultimo registro
                //------------------------------
                $legajo = Sicoss08::latest()->first();

                if ($legajo == null) {
                    $legajo = Sicoss08::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sicoss08;
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
            $legajo = new Sicoss08;

        // formulario vue via inertia
        return Inertia::render('Sicoss/Modalidades', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'empresa' => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render('Sicoss/Modalidades', [
            'legajo' => new Sicoss08(),   // id null, codigo null, etc
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
            'codigo' => 'required|integer|min:0|max:999999|unique:sicoss08s,codigo',
            'detalle' => 'required|string|max:100',
        ]);

        Sicoss08::create($validated);

        return redirect()->route('sicoss.modalidades.index')
            ->with('success', 'Modalidad creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $legajo = Sicoss08::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $legajo = Sicoss08::find($id);
            } elseif ($direction == -1) {
                $legajo = Sicoss08::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $legajo = Sicoss08::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $legajo = Sicoss08::orderBy('id', 'desc')->first();
            }

            if (!$legajo) {
                $legajo = Sicoss08::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Sicoss/Modalidades', [
            'legajo' => $legajo ?? new Sicoss08,
            'agregar' => false,
            'edicion' => false,
            'active' => 64,
            'empresa' => $empresa,
        ]);
    }

    public function edit(Sicoss08 $actividad)
    {
        if (!$actividad->exists) {
            return redirect()
                ->route('sicoss.modalidades.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('Sicoss/Modalidades', [
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
    public function update(Request $request, Sicoss08 $actividad)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:100',
        ]);

        $actividad->update($validated);

        return redirect()->route('sicoss.modalidades.index')
            ->with('success', 'Condición actualizada exitosamente.');
    }

    /**
     * Elimina un actividad de la base de datos
     */
    public function destroy(Sicoss08 $actividad)
    {
        $actividad->delete();

        return redirect()->route('sicoss.modalidades.index')
            ->with('success', 'Condición eliminada exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $legajo = Sicoss08::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.modalidades.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.modalidades.show', $legajo->id);
    }

    // Último registro
    public function last()
    {
        $legajo = Sicoss08::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.modalidades.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.modalidades.show', $legajo->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = Sicoss08::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('sicoss.modalidades.index', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('sicoss.modalidades.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = Sicoss08::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('sicoss.modalidades.index', $nextId)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('sicoss.modalidades.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = Sicoss08::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('apellido', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('legajo_numero', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Sicoss/Modalidades/Search', [
            'modalidades' => $query->paginate(20),
            'filters' => $request->only('search')
        ]);
    }
}
