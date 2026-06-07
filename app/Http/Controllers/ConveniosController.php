<?php

namespace App\Http\Controllers;

use App\Models\Sue007;
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * CRUD de Convenios Colectivos de Trabajo (CCT) — modelo Sue007.
 * Basado en el patrón de navegación de Legajos/Sicoss (first/last/previous/next + search).
 */
class ConveniosController extends Controller
{
    private const ACTIVE = 80;

    public function index($id = null, $direction = null)
    {
        $convenio = null;
        $agregar = false;
        $edicion = false;   // True: botones Grabar/Cancelar | False: Agregar/Modificar/Borrar

        if ($id == null || $id == 0) {
            $convenio = Sue007::orderBy('codigo')->first();
        } else {
            if ($direction == null) {
                $convenio = Sue007::find($id) ?? Sue007::orderBy('codigo')->first();
            } elseif ($direction == -1) {
                $convenio = Sue007::where('id', '<', $id)->orderBy('id', 'desc')->first()
                    ?? Sue007::orderBy('codigo')->first();
            } elseif ($direction == 1) {
                $convenio = Sue007::find($id) ?? Sue007::orderBy('codigo')->first();
            } elseif ($direction == -9) {
                $convenio = Sue007::latest()->first() ?? Sue007::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        if ($convenio == null) {
            $convenio = new Sue007;
        }

        return Inertia::render('Convenios/Index', [
            'convenio' => $convenio,
            'agregar'  => $agregar,
            'edicion'  => $edicion,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function create()
    {
        return Inertia::render('Convenios/Index', [
            'convenio' => new Sue007(),
            'agregar'  => true,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => Datoempr::first(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        Sue007::create($validated);

        return redirect()->route('convenios.index')
            ->with('success', 'Convenio creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $convenio = Sue007::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $convenio = Sue007::find($id);
            } elseif ($direction == -1) {
                $convenio = Sue007::where('id', '<', $id)->orderBy('id', 'desc')->first();
            } elseif ($direction == 1) {
                $convenio = Sue007::where('id', '>', $id)->orderBy('id', 'asc')->first();
            } elseif ($direction == -9) {
                $convenio = Sue007::orderBy('id', 'desc')->first();
            }

            if (!$convenio) {
                $convenio = Sue007::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Convenios/Index', [
            'convenio' => $convenio ?? new Sue007,
            'agregar'  => false,
            'edicion'  => false,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function edit(Sue007 $convenio)
    {
        if (!$convenio->exists) {
            return redirect()->route('convenios.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Convenios/Index', [
            'convenio' => $convenio,
            'agregar'  => false,
            'edicion'  => true,
            'active'   => self::ACTIVE,
            'empresa'  => $empresa,
        ]);
    }

    public function update(Request $request, Sue007 $convenio)
    {
        $validated = $this->validar($request, $convenio->id);

        $convenio->update($validated);

        return redirect()->route('convenios.index')
            ->with('success', 'Convenio actualizado exitosamente.');
    }

    public function destroy(Sue007 $convenio)
    {
        $convenio->delete();

        return redirect()->route('convenios.index')
            ->with('success', 'Convenio eliminado exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $convenio = Sue007::orderBy('codigo', 'asc')->first();

        if (!$convenio) {
            return redirect()->route('convenios.index')->with('error', 'No hay registros');
        }

        return redirect()->route('convenios.show', $convenio->id);
    }

    // Último registro
    public function last()
    {
        $convenio = Sue007::orderBy('codigo', 'desc')->first();

        if (!$convenio) {
            return redirect()->route('convenios.index')->with('error', 'No hay registros');
        }

        return redirect()->route('convenios.show', $convenio->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = Sue007::where('id', '<', $id)->orderBy('id', 'desc')->first()?->id;

        if (!$previousId) {
            return redirect()->route('convenios.index')->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('convenios.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = Sue007::where('id', '>', $id)->orderBy('id', 'asc')->first()?->id;

        if (!$nextId) {
            return redirect()->route('convenios.index')->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('convenios.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = Sue007::query();

        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('detalle', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Convenios/Search', [
            'convenios' => $query->orderBy('codigo')->paginate(20),
            'filters'   => $request->only('search'),
        ]);
    }

    /**
     * Reglas de validación compartidas entre store y update.
     */
    private function validar(Request $request, ?int $id = null): array
    {
        $uniqueCodigo = 'unique:sue007s,codigo' . ($id ? ',' . $id : '');

        $validated = $request->validate([
            'codigo'                   => 'required|string|max:5|' . $uniqueCodigo,
            'detalle'                  => 'required|string|max:30',
            'hs_normales_diarias'      => 'nullable|integer|min:0|max:24',
            'hs_normales_semanales'    => 'nullable|integer|min:0|max:168',
            'porc_tarea_dif'           => 'nullable|numeric|min:0|max:100',
            'noct_100'                 => 'nullable|boolean',
            'forzar50'                 => 'nullable|string|max:40',
            'bh_habilitado'            => 'nullable|boolean',
            'bh_tope_saldo_positivo'   => 'nullable|numeric|min:0',
            'bh_meses_vencimiento'     => 'nullable|integer|min:0',
            'bh_al_vencer'             => 'nullable|in:pierde,paga_extra',
            'bh_convierte_a_extra_pct' => 'nullable|numeric|min:0|max:100',
            'bh_cod_nov_franco'        => 'nullable|string|max:6',
            'bh_cod_nov_paga_extra'    => 'nullable|string|max:6',
            'je_habilitada'            => 'nullable|boolean',
            'je_hs_normales'           => 'nullable|numeric|min:0',
            'je_hs_dobles'             => 'nullable|numeric|min:0',
            'je_cod_nov_doble'         => 'nullable|string|max:6',
        ], [
            'codigo.required'  => 'El código es obligatorio.',
            'codigo.unique'    => 'Ya existe un convenio con ese código.',
            'detalle.required' => 'El nombre del convenio es obligatorio.',
        ]);

        // Columnas NOT NULL: garantizar un valor válido aunque el form no lo envíe
        $validated['bh_habilitado'] = $request->boolean('bh_habilitado');
        $validated['je_habilitada'] = $request->boolean('je_habilitada');
        $validated['noct_100']      = $request->boolean('noct_100');
        $validated['bh_al_vencer']  = $validated['bh_al_vencer'] ?? 'pierde';

        return $validated;
    }
}
