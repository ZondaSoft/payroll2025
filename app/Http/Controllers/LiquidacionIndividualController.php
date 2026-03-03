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
        $legajos  = Sue001::orderBy('codigo')->get(['id', 'codigo', 'detalle', 'nombres', 'cuil']);
        $periodos = Sue100::orderBy('periodo', 'desc')->get(['id', 'periodo']);

        $empleado  = null;
        $conceptos = [];
        $periodoStr = $request->get('periodo', '');
        $legajoId   = $request->get('legajo_id', null);

        if ($legajoId) {
            $empleado = Sue001::with(['sector', 'jerarquia', 'ccosto'])->find($legajoId);
        }

        if ($empleado && $periodoStr) {
            $rows = DB::table('sue090s')
                ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                ->where('sue090s.legajo', $empleado->codigo)
                ->where('sue090s.periodo', $periodoStr)
                ->orderBy('sue102s.orden_impresion')
                ->orderBy('sue090s.concepto')
                ->select(
                    'sue090s.concepto        as codigo',
                    'sue102s.detalle         as detalle',
                    'sue090s.cantidad',
                    'sue102s.tipo',
                    'sue090s.importe',
                    'sue090s.tiporem',
                )
                ->get();

            foreach ($rows as $row) {
                $tipo    = $row->tipo ?? 0;
                $importe = abs($row->importe ?? 0);

                $conceptos[] = [
                    'codigo'          => $row->codigo,
                    'detalle'         => $row->detalle ?? "Concepto {$row->codigo}",
                    'cantidad'        => $row->cantidad,
                    'valores'         => null,
                    'haberes'         => in_array($tipo, [1]) ? $importe : null,
                    'retenciones'     => in_array($tipo, [2, 5, 8]) ? $importe : null,
                    'asignaciones'    => in_array($tipo, [3]) ? $importe : null,
                    'no_remunerativo' => in_array($tipo, [4, 6]) ? $importe : null,
                ];
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
        ]);
    }
}
