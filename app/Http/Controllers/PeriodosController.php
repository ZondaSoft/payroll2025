<?php

namespace App\Http\Controllers;

use App\Models\Sue100;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PeriodosController extends Controller
{
    public function index($id = null, $direction = null)
    {
        $periodo = null;
        $agregar = false;
        $edicion = false;

        if ($id == null || $id == 0) {
            $periodo = Sue100::orderBy('periodo', 'desc')->first();
        } else {
            if ($direction == null) {
                $periodo = Sue100::find($id);
                if (!$periodo) {
                    $periodo = Sue100::orderBy('periodo', 'desc')->first();
                }
            } elseif ($direction == -1) {
                $periodo = Sue100::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($periodo == null) {
                    $periodo = Sue100::orderBy('periodo', 'desc')->first();
                }
            } elseif ($direction == 1) {
                $periodo = Sue100::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
                if ($periodo == null) {
                    $periodo = Sue100::orderBy('periodo', 'desc')->first();
                }
            } elseif ($direction == -9) {
                $periodo = Sue100::orderBy('id', 'asc')->first();
                if ($periodo == null) {
                    $periodo = Sue100::orderBy('periodo', 'desc')->first();
                }
            }
        }

        if ($periodo == null) {
            $periodo = new Sue100();
        }

        return Inertia::render('Liquidacion/Periodos', [
            'periodo' => $periodo,
            'agregar' => $agregar,
            'edicion' => $edicion,
        ]);
    }

    public function create()
    {
        return Inertia::render('Liquidacion/Periodos', [
            'periodo' => new Sue100(),
            'agregar' => true,
            'edicion' => true,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Único por (período, tipoliq): un mismo mes admite Normal + SAC + Liq. Final,
            // pero no dos encabezados del mismo tipo (duplicaba el selector de períodos).
            'periodo'        => ['required', 'string', 'max:6',
                Rule::unique('sue100s', 'periodo')->where(fn ($q) => $q->where('tipoliq', (int) $request->input('tipoliq')))],
            'tipoliq'        => 'required|integer|in:1,2,3,4,5',
            'fecha'          => 'required|date',
            'fecha_pago'     => 'nullable|date',
            'banco'          => 'nullable|string|max:5',
            'fecha_aportes'  => 'nullable|date',
            'periodo_aporte' => 'nullable|string|max:6',
            'comentarios'    => 'nullable|string|max:255',
            'estado'         => 'nullable|string|max:20',
        ], [
            'periodo.required' => 'El período es obligatorio.',
            'periodo.unique'   => 'Ya existe un período con ese código y tipo de liquidación.',
            'tipoliq.required' => 'El tipo de liquidación es obligatorio.',
            'tipoliq.in'       => 'El tipo de liquidación debe ser uno de los valores válidos.',
            'fecha.required'   => 'La fecha es obligatoria.',
        ]);

        $validated['user'] = auth()->id();

        Sue100::create($validated);

        return redirect()->route('liquidacion.periodos.index')
            ->with('success', 'Período creado exitosamente.');
    }

    public function show($id)
    {
        $periodo = Sue100::find($id);
        if (!$periodo) {
            $periodo = Sue100::orderBy('periodo', 'desc')->first();
        }

        return Inertia::render('Liquidacion/Periodos', [
            'periodo' => $periodo ?? new Sue100(),
            'agregar' => false,
            'edicion' => false,
        ]);
    }

    public function edit(Sue100 $periodo)
    {
        if (!$periodo->exists) {
            return redirect()
                ->route('liquidacion.periodos.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        return Inertia::render('Liquidacion/Periodos', [
            'periodo' => $periodo,
            'agregar' => false,
            'edicion' => true,
        ]);
    }

    public function update(Request $request, Sue100 $periodo)
    {
        $validated = $request->validate([
            // Único por (período, tipoliq), ignorando el propio registro al editar.
            'periodo'        => ['required', 'string', 'max:6',
                Rule::unique('sue100s', 'periodo')->where(fn ($q) => $q->where('tipoliq', (int) $request->input('tipoliq')))->ignore($periodo->id)],
            'tipoliq'        => 'required|integer|in:1,2,3,4,5',
            'fecha'          => 'required|date',
            'fecha_pago'     => 'nullable|date',
            'banco'          => 'nullable|string|max:5',
            'fecha_aportes'  => 'nullable|date',
            'periodo_aporte' => 'nullable|string|max:6',
            'comentarios'    => 'nullable|string|max:255',
            'estado'         => 'nullable|string|max:20',
        ], [
            'periodo.required' => 'El período es obligatorio.',
            'periodo.unique'   => 'Ya existe un período con ese código y tipo de liquidación.',
            'tipoliq.required' => 'El tipo de liquidación es obligatorio.',
            'tipoliq.in'       => 'El tipo de liquidación debe ser uno de los valores válidos.',
            'fecha.required'   => 'La fecha es obligatoria.',
        ]);

        $periodo->update($validated);

        return redirect()->route('liquidacion.periodos.show', $periodo->id)
            ->with('success', 'Período actualizado exitosamente.');
    }

    public function destroy(Sue100 $periodo)
    {
        $periodo->delete();

        return redirect()->route('liquidacion.periodos.index')
            ->with('success', 'Período eliminado exitosamente.');
    }

    public function first()
    {
        $periodo = Sue100::orderBy('periodo', 'asc')->first();

        if (!$periodo) {
            return redirect()->route('liquidacion.periodos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('liquidacion.periodos.show', $periodo->id);
    }

    public function last()
    {
        $periodo = Sue100::orderBy('periodo', 'desc')->first();

        if (!$periodo) {
            return redirect()->route('liquidacion.periodos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('liquidacion.periodos.show', $periodo->id);
    }

    public function previous($id)
    {
        $current = Sue100::findOrFail($id);

        $previous = Sue100::where('periodo', '<', $current->periodo)
            ->orderBy('periodo', 'desc')
            ->first();

        if (!$previous) {
            return redirect()->route('liquidacion.periodos.show', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('liquidacion.periodos.show', $previous->id);
    }

    public function next($id)
    {
        $current = Sue100::findOrFail($id);

        $next = Sue100::where('periodo', '>', $current->periodo)
            ->orderBy('periodo', 'asc')
            ->first();

        if (!$next) {
            return redirect()->route('liquidacion.periodos.show', $id)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('liquidacion.periodos.show', $next->id);
    }

    public function search(Request $request)
    {
        $query = Sue100::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('periodo', 'LIKE', "%{$search}%")
                  ->orWhere('comentarios', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Liquidacion/PeriodosSearch', [
            'periodos' => $query->orderBy('periodo', 'desc')->paginate(20),
            'filters'  => $request->only('search'),
        ]);
    }
}
