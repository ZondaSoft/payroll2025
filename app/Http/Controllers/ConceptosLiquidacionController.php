<?php

namespace App\Http\Controllers;

use App\Models\Sue102;
use App\Models\Sue089;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class ConceptosLiquidacionController extends Controller
{
    public function index($id = null, $direction = null)
    {
        // Corrige conceptos con tipo numérico legacy según los Rangos de conceptos (sue089s).
        $ajustesTipos = Sue102::normalizarTiposNumericos();

        $concepto = null;
        $agregar = false;
        $edicion = false;
        $active = 65;

        $user = auth()->user();
        $roles = $user->getRoleNames();
        $rol = $roles->first() ?? '';

        // Busco registro
        if ($id == null || $id == 0) {
            $concepto = Sue102::orderBy('codigo')->first();
        } else {
            if ($direction == null) {
                $concepto = Sue102::find($id);
                if (!$concepto) {
                    $concepto = Sue102::orderBy('codigo')->first();
                }
            } elseif ($direction == -1) {
                $concepto = Sue102::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($concepto == null) {
                    $concepto = Sue102::orderBy('codigo')->first();
                }
            } elseif ($direction == 1) {
                $concepto = Sue102::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($concepto == null) {
                    $concepto = Sue102::orderBy('codigo')->first();
                }
            } elseif ($direction == -9) {
                $concepto = Sue102::orderBy('id', 'desc')->first();

                if ($concepto == null) {
                    $concepto = Sue102::orderBy('codigo')->first();
                }
            }
        }

        $empresa = Datoempr::first();
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        if ($concepto == null) {
            $concepto = new Sue102();
        }

        return Inertia::render('Liquidacion/Conceptos', [
            'concepto' => $concepto,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'empresa' => $empresa,
            'csrf_token' => csrf_token(),
            'ajustesTipos' => $ajustesTipos,
        ]);
    }

    public function create(Request $request)
    {
        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        // Permite precargar el código (ej. al venir desde "conceptos sin parametrizar" del LSD)
        $concepto = new Sue102();
        if ($request->filled('codigo')) {
            $concepto->codigo = (int) $request->query('codigo');
        }

        return Inertia::render('Liquidacion/Conceptos', [
            'concepto' => $concepto,
            'agregar' => true,
            'edicion' => true,
            'active' => 65,
            'empresa' => $empresa,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Almacena un nuevo concepto en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|integer|min:0|max:999999|unique:sue102s,codigo',
            'detalle' => 'required|string|max:250',
            'tipo' => 'required|string|in:H,D,NR,AS,GC,DG,R,AP,AU',
            'formula' => 'nullable|string',
            'porcentaje' => 'nullable|numeric|min:0|max:100',
            'importe_fijo' => 'nullable|numeric|min:0',
            'imponible' => 'boolean',
            'afecta_sac' => 'boolean',
            'afecta_vacaciones' => 'boolean',
            'imprime_recibo' => 'boolean',
            'orden_impresion' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'cuenta_contable' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'sicoss_afecta' => 'boolean',
            'gcias_afecta' => 'boolean',
            'concepto_arca' => 'nullable|string',
        ], [
            'tipo.required' => 'El tipo de concepto es obligatorio.',
            'tipo.string' => 'El tipo de concepto es obligatorio.',
            'tipo.in'       => 'El tipo debe ser uno de los siguientes valores: H, D, NR, AS, GC, DG, R, AP, AU.',
        ]);

        // Recortar concepto_arca a 6 caracteres
        if (isset($validated['concepto_arca']) && $validated['concepto_arca']) {
            $validated['concepto_arca'] = substr($validated['concepto_arca'], 0, 6);
        }

        Sue102::create($validated);

        return redirect()->route('liquidacion.conceptos.index')
            ->with('success', 'Concepto de liquidación creado exitosamente.');
    }

    public function show($id, $direction = null)
    {
        if (!$id || $id == 0) {
            $concepto = Sue102::orderBy('codigo')->first();
        } else {
            if ($direction === null) {
                $concepto = Sue102::find($id);
            } elseif ($direction == -1) {
                $concepto = Sue102::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();
            } elseif ($direction == 1) {
                $concepto = Sue102::where('id', '>', $id)
                    ->orderBy('id', 'asc')
                    ->first();
            } elseif ($direction == -9) {
                $concepto = Sue102::orderBy('id', 'desc')->first();
            }

            if (!$concepto) {
                $concepto = Sue102::orderBy('codigo')->first();
            }
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Liquidacion/Conceptos', [
            'concepto' => $concepto ?? new Sue102(),
            'agregar' => false,
            'edicion' => false,
            'active' => 65,
            'empresa' => $empresa,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function edit(Sue102 $concepto)
    {
        if (!$concepto->exists) {
            return redirect()
                ->route('liquidacion.conceptos.index')
                ->with('warning', 'No hay registros para modificar.');
        }

        $empresa = Datoempr::first();
        if (!$empresa) {
            return redirect('/empresa/');
        }

        return Inertia::render('Liquidacion/Conceptos', [
            'concepto' => $concepto,
            'agregar' => false,
            'edicion' => true,
            'active' => 65,
            'empresa' => $empresa,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Actualiza un concepto en la base de datos
     */
    public function update(Request $request, Sue102 $concepto)
    {
        $validated = $request->validate([
            'detalle' => 'required|string|max:250',
            'tipo' => 'required|string|in:H,D,NR,AS,GC,DG,R,AP,AU',
            'formula' => 'nullable|string',
            'porcentaje' => 'nullable|numeric|min:0|max:100',
            'importe_fijo' => 'nullable|numeric|min:0',
            'imponible' => 'boolean',
            'afecta_sac' => 'boolean',
            'afecta_vacaciones' => 'boolean',
            'imprime_recibo' => 'boolean',
            'orden_impresion' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'cuenta_contable' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'sicoss_afecta' => 'boolean',
            'gcias_afecta' => 'boolean',
            'concepto_arca' => 'nullable|string',
        ], [
            'tipo.required' => 'El tipo de concepto es obligatorio.',
            'tipo.string' => 'El tipo de concepto es obligatorio.',
            'tipo.in'       => 'El tipo debe ser uno de los siguientes valores: H, D, NR, AS, GC, DG, R, AP, AU.',
        ]);

        // Recortar concepto_arca a 6 caracteres
        if (isset($validated['concepto_arca']) && $validated['concepto_arca']) {
            $validated['concepto_arca'] = substr($validated['concepto_arca'], 0, 6);
        }

        $concepto->update($validated);

        return redirect()->route('liquidacion.conceptos.show', $concepto->id)
            ->with('success', 'Concepto de liquidación actualizado exitosamente.');
    }

    /**
     * Elimina un concepto de la base de datos
     */
    public function destroy(Sue102 $concepto)
    {
        $concepto->delete();

        return redirect()->route('liquidacion.conceptos.index')
            ->with('success', 'Concepto de liquidación eliminado exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $concepto = Sue102::orderBy('id', 'asc')->first();

        if (!$concepto) {
            return redirect()->route('liquidacion.conceptos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('liquidacion.conceptos.show', $concepto->id);
    }

    // Último registro
    public function last()
    {
        $concepto = Sue102::orderBy('id', 'desc')->first();

        if (!$concepto) {
            return redirect()->route('liquidacion.conceptos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('liquidacion.conceptos.show', $concepto->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = Sue102::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('liquidacion.conceptos.index', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('liquidacion.conceptos.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = Sue102::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('liquidacion.conceptos.index', $nextId)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('liquidacion.conceptos.show', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = Sue102::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('detalle', 'LIKE', "%{$search}%")
                  ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Liquidacion/Search', [
            'conceptos' => $query->orderBy('codigo')->paginate(20),
            'filters' => $request->only('search'),
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Obtener el próximo código disponible para un tipo
     */
    public function obtenerProximoCodigo(Request $request)
    {
        $tipo = $request->input('tipo');

        if (!$tipo || !in_array($tipo, ['H', 'D', 'AS', 'NR', 'GC', 'DG', 'R', 'AP', 'AU'])) {
            return response()->json([
                'error' => 'Tipo inválido'
            ], 400);
        }

        // Obtener el rango de Sue089
        $rango = Sue089::where('tiporem', $tipo)->first();

        if (!$rango) {
            return response()->json([
                'error' => 'No existe rango configurado para este tipo'
            ], 400);
        }

        // Buscar el primer código libre del rango. El código es único en sue102s sin importar
        // el tipo, por eso se consideran ocupados todos los códigos del rango numérico.
        // (Antes se proponía "máximo usado + 1": si el tope del rango ya estaba usado —ej. el
        // 199 en H— devolvía "sin códigos disponibles" aunque hubiera huecos libres en el medio.)
        $usados = Sue102::whereBetween('codigo', [$rango->desde, $rango->hasta])
            ->pluck('codigo')
            ->map(fn ($c) => (int) $c)
            ->flip();

        $codigo = (int) $rango->desde;
        $hasta = (int) $rango->hasta;
        while ($codigo <= $hasta && isset($usados[$codigo])) {
            $codigo++;
        }

        if ($codigo > $hasta) {
            return response()->json([
                'error' => "No hay más códigos disponibles para el tipo {$tipo}. Rango: {$rango->desde} a {$rango->hasta}"
            ], 400);
        }

        return response()->json([
            'codigo' => $codigo,
            'desde' => $rango->desde,
            'hasta' => $rango->hasta,
        ]);
    }

    /**
     * Buscar conceptos Arca para select2
     */
    public function buscarConceptosArca(Request $request)
    {
        $search = $request->input('search', '');

        // Catálogo real de conceptos ARCA: tabla `conceptosarcas` (Conceptosarca).
        // `sicoss_conceptos_arcas` quedó sin uso/vacía; usarla dejaba el autocomplete sin opciones.
        $query = \App\Models\Conceptosarca::query()->whereNotNull('codigo_afip');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo_afip', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $conceptos = $query
            ->orderBy('codigo_afip')
            ->get(['codigo_afip', 'descripcion'])
            ->unique('codigo_afip')   // el catálogo se repite por empresa: dedup por código ARCA
            ->take(100)
            ->map(function ($concepto) {
                // id como string (6 dígitos) para que matchee con sue102s.concepto_arca (varchar)
                $codigo = str_pad((string) $concepto->codigo_afip, 6, '0', STR_PAD_LEFT);
                return [
                    'id' => $codigo,
                    'text' => "{$codigo} - {$concepto->descripcion}",
                ];
            })
            ->values();

        return response()->json(['results' => $conceptos]);
    }
}
