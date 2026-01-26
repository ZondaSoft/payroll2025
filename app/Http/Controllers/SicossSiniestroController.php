<?php

namespace App\Http\Controllers;

use App\Models\SicossSinie;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class SicossSiniestroController extends Controller
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
            $legajo = SicossSinie::orderBy('codigo')
                ->first();      // find($id);
        } else {
            //------------------------------
            //      Primer registro
            //------------------------------
            if ($direction == null) {
                $legajo = SicossSinie::find($id);
                if (!$legajo) {
                    $legajo = SicossSinie::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossSinie;
                }
            } elseif ($direction == -1) {
                //------------------------------
                //      Registro anterior
                //------------------------------
                $legajo = SicossSinie::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($legajo == null) {
                    $legajo = SicossSinie::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossSinie;
                }
            } elseif ($direction == 1) {
                //------------------------------
                //      Registro siguiente
                //------------------------------
                $legajo = SicossSinie::find($id);
                if ($legajo == null) {
                    $legajo = SicossSinie::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossSinie;
                }

            } elseif ($direction == -9) {
                //------------------------------
                //      Ultimo registro
                //------------------------------
                $legajo = SicossSinie::latest()->first();

                if ($legajo == null) {
                    $legajo = SicossSinie::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new SicossSinie;
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
            $legajo = new SicossSinie;

        // formulario vue via inertia
        return Inertia::render('Sicoss/Sinie', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'empresa' => $empresa,
        ]);
    }

    
    public function create()
    {
        return Inertia::render('Sicoss/Sinie', [
            'legajo' => new SicossSinie(),   // id null, codigo null, etc
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

        SicossSinie::create($validated);

        return redirect()->route('sicoss.siniestros.index')
            ->with('success', 'Modalidad creada exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $legajo = SicossSinie::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $legajo = SicossSinie::find($id);
            } elseif ($direction == -1) {
                $legajo = SicossSinie::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $legajo = SicossSinie::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $legajo = SicossSinie::orderBy('id', 'desc')->first();
            }

            if (!$legajo) {
                $legajo = SicossSinie::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Sicoss/Sinie', [
            'legajo' => $legajo ?? new SicossSinie,
            'agregar' => false,
            'edicion' => false,
            'active' => 64,
            'empresa' => $empresa,
        ]);
    }

    public function edit(SicossSinie $actividad)
    {
        if (!$actividad->exists) {
            return redirect()
                ->route('sicoss.siniestros.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('Sicoss/Sinie', [
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
    public function update(Request $request, SicossSinie $actividad)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:100',
        ]);

        $actividad->update($validated);

        return redirect()->route('sicoss.siniestros.index')
            ->with('success', 'Condición actualizada exitosamente.');
    }

    /**
     * Elimina un actividad de la base de datos
     */
    public function destroy(SicossSinie $actividad)
    {
        $actividad->delete();

        return redirect()->route('sicoss.siniestros.index')
            ->with('success', 'Condición eliminada exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $legajo = SicossSinie::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.siniestros.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.siniestros.show', $legajo->id);
    }

    // Último registro
    public function last()
    {
        $legajo = SicossSinie::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('sicoss.siniestros.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('sicoss.siniestros.show', $legajo->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = SicossSinie::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('sicoss.siniestros.index', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('sicoss.siniestros.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = SicossSinie::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('sicoss.siniestros.index', $nextId)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('sicoss.siniestros.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = SicossSinie::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('apellido', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('legajo_numero', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Sicoss/siniestros/Search', [
            'siniestros' => $query->paginate(20),
            'filters' => $request->only('search')
        ]);
    }
}
