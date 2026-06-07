<?php

namespace App\Http\Controllers;

use App\Models\Conceptosarca;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConceptosArcaController extends Controller
{
    private const ACTIVE = 65;

    /** Campos de alícuotas (decimal 9,3) validados como numéricos nullable. */
    private function reglasTasas(): array
    {
        $campos = [
            'marca_repetible',
            'aportes_sipa', 'contribuciones_sipa',
            'aportes_inssjyp', 'contribuciones_inssjyp',
            'aportes_obra_social', 'contribuciones_obra_social',
            'aportes_fsr', 'contribuciones_fsr',
            'aportes_renatea', 'contribuciones_renatea',
            'contribuciones_aaff', 'contribuciones_fne', 'contribuciones_lrt',
            'aportes_diferenciales', 'aportes_especiales',
        ];

        return array_fill_keys($campos, 'nullable|numeric');
    }

    public function index($id = null)
    {
        if ($id == null || $id == 0) {
            $concepto = Conceptosarca::orderBy('codigo_contribuyente')->first();
        } else {
            $concepto = Conceptosarca::find($id)
                ?? Conceptosarca::orderBy('codigo_contribuyente')->first();
        }

        return Inertia::render('ConceptosArca/Index', [
            'legajo'  => $concepto ?? new Conceptosarca,
            'agregar' => false,
            'edicion' => false,
            'active'  => self::ACTIVE,
        ]);
    }

    public function create()
    {
        return Inertia::render('ConceptosArca/Index', [
            'legajo'  => new Conceptosarca(),
            'agregar' => true,
            'edicion' => true,
            'active'  => self::ACTIVE,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge([
            'codigo_afip'               => 'nullable|integer',
            'descripcion'               => 'nullable|string|max:80',
            'codigo_contribuyente'      => 'required|integer|unique:conceptosarcas,codigo_contribuyente',
            'descripcion_contribuyente' => 'nullable|string|max:80',
        ], $this->reglasTasas()), [
            'codigo_contribuyente.required' => 'El código de contribuyente es obligatorio.',
            'codigo_contribuyente.integer'  => 'El código de contribuyente debe ser numérico.',
            'codigo_contribuyente.unique'   => 'Ya existe un concepto con ese código de contribuyente.',
            'codigo_afip.integer'           => 'El código ARCA debe ser numérico.',
            'descripcion.max'               => 'La descripción no puede superar los 80 caracteres.',
            'descripcion_contribuyente.max' => 'La descripción del contribuyente no puede superar los 80 caracteres.',
        ]);

        Conceptosarca::create($validated);

        return redirect()->route('arca.conceptos.index')
            ->with('success', 'Concepto ARCA creado exitosamente.');
    }

    public function show($id)
    {
        $concepto = $id && $id != 0
            ? Conceptosarca::find($id)
            : Conceptosarca::orderBy('codigo_contribuyente')->first();

        return Inertia::render('ConceptosArca/Index', [
            'legajo'  => $concepto ?? new Conceptosarca,
            'agregar' => false,
            'edicion' => false,
            'active'  => self::ACTIVE,
        ]);
    }

    public function edit(Conceptosarca $concepto)
    {
        if (!$concepto->exists) {
            return redirect()->route('arca.conceptos.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        return Inertia::render('ConceptosArca/Index', [
            'legajo'  => $concepto,
            'agregar' => false,
            'edicion' => true,
            'active'  => self::ACTIVE,
        ]);
    }

    public function update(Request $request, Conceptosarca $concepto)
    {
        // codigo_contribuyente es la clave inmutable: no se valida ni actualiza.
        $validated = $request->validate(array_merge([
            'codigo_afip'               => 'nullable|integer',
            'descripcion'               => 'nullable|string|max:80',
            'descripcion_contribuyente' => 'nullable|string|max:80',
        ], $this->reglasTasas()), [
            'codigo_afip.integer'           => 'El código ARCA debe ser numérico.',
            'descripcion.max'               => 'La descripción no puede superar los 80 caracteres.',
            'descripcion_contribuyente.max' => 'La descripción del contribuyente no puede superar los 80 caracteres.',
        ]);

        $concepto->update($validated);

        return redirect()->route('arca.conceptos.index')
            ->with('success', 'Concepto ARCA actualizado exitosamente.');
    }

    public function destroy(Conceptosarca $concepto)
    {
        $concepto->delete();

        return redirect()->route('arca.conceptos.index')
            ->with('success', 'Concepto ARCA eliminado exitosamente.');
    }

    public function first()
    {
        $concepto = Conceptosarca::orderBy('id', 'asc')->first();

        if (!$concepto) {
            return redirect()->route('arca.conceptos.index')->with('error', 'No hay registros');
        }

        return redirect()->route('arca.conceptos.show', $concepto->id);
    }

    public function last()
    {
        $concepto = Conceptosarca::orderBy('id', 'desc')->first();

        if (!$concepto) {
            return redirect()->route('arca.conceptos.index')->with('error', 'No hay registros');
        }

        return redirect()->route('arca.conceptos.show', $concepto->id);
    }

    public function previous($id)
    {
        $previousId = Conceptosarca::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('arca.conceptos.show', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('arca.conceptos.show', $previousId);
    }

    public function next($id)
    {
        $nextId = Conceptosarca::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('arca.conceptos.show', $id)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('arca.conceptos.show', $nextId);
    }

    public function search(Request $request)
    {
        $query = Conceptosarca::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('codigo_contribuyente', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion_contribuyente', 'LIKE', "%{$search}%")
                  ->orWhere('codigo_afip', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('ConceptosArca/Search', [
            'conceptos' => $query->orderBy('codigo_contribuyente')->paginate(20),
            'filters'   => $request->only('search'),
            'active'    => self::ACTIVE,
        ]);
    }
}
