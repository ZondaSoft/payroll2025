<?php

namespace App\Http\Controllers;

use App\Models\Sue001;
use App\Models\Sue086;
use App\Models\Sue100;
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LiquidacionIndividualController extends Controller
{
    public function index(Request $request)
    {
        $empresa  = Datoempr::first();
        $periodos = Sue100::orderBy('id', 'desc')->get(['id', 'periodo', 'tipoliq']);
        $periodoActual = $periodos->first()?->periodo;
        $tipoliqActual = $periodos->first()?->tipoliq;
        $periodoStr = $request->get('periodo', '');
        $legajoId   = $request->get('legajo_id', null);
        $tipoliq    = $request->get('tipoliq', '');

        if ($tipoliq !== '' && $tipoliq !== null) {
            $tipoliq = (int) $tipoliq;
        } else {
            $tipoliq = null;
        }

        // Primera carga sin filtros: usar último período generado y su tipoliq
        if (!$periodoStr) {
            $periodoStr = $periodoActual ?? '';
        }
        if ($tipoliq === null && $periodoStr) {
            $tipoliq = $tipoliqActual;
        }

        $periodoFiltro = $periodoStr ?: $periodoActual;
        $filterByPeriodo = $request->boolean('filter_by_periodo', false);

        $legajosQuery = Sue001::query();

        if ($periodoFiltro && $filterByPeriodo) {
            // Mostrar solo legajos que tienen liquidación en sue090s para el período y tipoliq
            $legajosQuery->whereExists(function ($sub) use ($periodoStr, $tipoliq) {
                $sub->from('sue090s')
                    ->whereColumn('sue090s.legajo', 'sue001s.codigo')
                    ->where('sue090s.periodo', $periodoStr)
                    ->where('sue090s.tipoliq', $tipoliq);
            });
        } elseif ($periodoFiltro) {
            // Extraer año y mes del período de filtro (YYYYMM -> YYYY-MM)
            $anioPeriodo = substr($periodoFiltro, 0, 4);
            $mesPeriodo  = substr($periodoFiltro, 4, 2);
            $fechaPeriodo = $anioPeriodo . '-' . $mesPeriodo;

            // Incluir activos y bajas dentro del período seleccionado
            $legajosQuery->where(function ($query) use ($fechaPeriodo) {
                $query->whereNull('baja')
                    ->orWhereRaw("DATE_FORMAT(baja, '%Y-%m') = ?", [$fechaPeriodo]);
            });
        } else {
            // Si no hay período actual, al menos mostrar legajos activos
            $legajosQuery->whereNull('baja');
        }

        $legajos = $legajosQuery
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'detalle', 'nombres', 'cuil']);

        $empleado  = null;
        $conceptos = [];

        if ($legajoId) {
            $empleado = Sue001::with(['sector', 'jerarquia', 'ccosto', 'convenios', 'obraSijp', 'grupoEmpresario'])->find($legajoId);
        }

        if ($empleado && $periodoStr) {
            $rows = DB::table('sue090s')
                ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                ->where('sue090s.legajo', $empleado->codigo)
                ->where('sue090s.periodo', $periodoStr)
                ->when($tipoliq !== null, function ($query) use ($tipoliq) {
                    $query->where('sue090s.tipoliq', $tipoliq);
                })
                ->orderByRaw('CAST(sue090s.concepto AS UNSIGNED)')
                ->select(
                    'sue090s.concepto        as codigo',
                    'sue102s.detalle         as detalle',
                    'sue090s.descripcion     as descripcion',
                    'sue090s.cantidad',
                    'sue102s.tipo',
                    'sue090s.importe',
                    'sue090s.tiporem',
                )
                ->get();

            foreach ($rows as $row) {
                $tipo    = $row->tipo ?? '';
                $importe = abs($row->importe ?? 0);

                // Clasificar según el tipo (H, D, AS, NR, etc.)
                $haberes = null;
                $retenciones = null;
                $asignaciones = null;
                $noRemunerativo = null;

                // Busco el tipo en la tabla de topes Sue089s
                if (!$tipo) {
                    $tope = DB::table('sue089s')
                        ->where('desde', '<=', $row->codigo)
                        ->where('hasta', '>=', $row->codigo)
                        ->first();
                    if ($tope) {
                        $tipo = $tope->tiporem; // Reemplazo el tipo por el valor real (H, D, AS, NR)
                    }
                }

                if ($tipo === 'H') {
                    $haberes = $importe;
                } elseif ($tipo === 'D') {
                    $retenciones = $importe;
                } elseif ($tipo === 'AS') {
                    $asignaciones = $importe;
                } else {
                    // NR y cualquier otra letra va a no remunerativo
                    $noRemunerativo = $importe;
                }

                $conceptos[] = [
                    'codigo'          => $row->codigo,
                    'detalle'         => $row->detalle ?? $row->descripcion ?? "Concepto {$row->codigo}",
                    'cantidad'        => $row->cantidad,
                    'valores'         => null,
                    'haberes'         => $haberes,
                    'retenciones'     => $retenciones,
                    'asignaciones'    => $asignaciones,
                    'no_remunerativo' => $noRemunerativo,
                ];
            }
        }

        // Si no hay legajo seleccionado, usar el primero encontrado
        $legajoDefault = null;
        if (!$legajoId && $legajos->count() > 0) {
            $legajoDefault = $legajos->first();
            $legajoId = $legajoDefault->id;
            $periodoStr = $periodoStr ?: ($periodoActual ?? '');
            $tipoliq = $tipoliq ?? $tipoliqActual;
            
            // Cargar empleado y conceptos automáticamente
            $empleado = Sue001::with(['sector', 'jerarquia', 'ccosto', 'convenios', 'obraSijp', 'grupoEmpresario'])->find($legajoId);
            
            if ($empleado && $periodoStr) {
                $rows = DB::table('sue090s')
                    ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                    ->where('sue090s.legajo', $empleado->codigo)
                    ->where('sue090s.periodo', $periodoStr)
                    ->when($tipoliq !== null, function ($query) use ($tipoliq) {
                        $query->where('sue090s.tipoliq', $tipoliq);
                    })
                    ->orderByRaw('CAST(sue090s.concepto AS UNSIGNED)')
                    ->select(
                        'sue090s.concepto        as codigo',
                        'sue102s.detalle         as detalle',
                        'sue090s.descripcion     as descripcion',
                        'sue090s.cantidad',
                        'sue102s.tipo',
                        'sue090s.importe',
                        'sue090s.tiporem',
                    )
                    ->get();

                foreach ($rows as $row) {
                    $tipo    = $row->tipo ?? '';
                    $importe = abs($row->importe ?? 0);

                    // Clasificar según el tipo (H, D, AS, NR, etc.)
                    $haberes = null;
                    $retenciones = null;
                    $asignaciones = null;
                    $noRemunerativo = null;

                    // Busco el tipo en la tabla de topes Sue089s
                    if (!$tipo) {
                        $tope = DB::table('sue089s')
                            ->where('desde', '<=', $row->codigo)
                            ->where('hasta', '>=', $row->codigo)
                            ->first();
                        if ($tope) {
                            $tipo = $tope->tiporem; // Reemplazo el tipo por el valor real (H, D, AS, NR)
                        }
                    }

                    if ($tipo === 'H') {
                        $haberes = $importe;
                    } elseif ($tipo === 'D') {
                        $retenciones = $importe;
                    } elseif ($tipo === 'AS') {
                        $asignaciones = $importe;
                    } else {
                        // NR y cualquier otra letra va a no remunerativo
                        $noRemunerativo = $importe;
                    }

                    $conceptos[] = [
                        'codigo'          => $row->codigo,
                        'detalle'         => $row->detalle ?? $row->descripcion ?? "Concepto {$row->codigo}",
                        'cantidad'        => $row->cantidad,
                        'valores'         => null,
                        'haberes'         => $haberes,
                        'retenciones'     => $retenciones,
                        'asignaciones'    => $asignaciones,
                        'no_remunerativo' => $noRemunerativo,
                    ];
                }
            }
        }

        return Inertia::render('Liquidacion/LiquidacionIndividual', [
            'empleado'   => $empleado,
            'conceptos'  => $conceptos,
            'periodo'    => $periodoStr,
            'empresa'    => $empresa,
            'legajos'    => $legajos,
            'periodos'   => $periodos,
            'legajoId'   => $legajoId ? (int) $legajoId : null,
            'tipoliq'    => $tipoliq,
        ]);
    }
}
