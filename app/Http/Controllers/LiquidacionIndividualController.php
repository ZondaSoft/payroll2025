<?php

namespace App\Http\Controllers;

use App\Models\Sue001;
use App\Models\Sue086;
use App\Models\Sue100;
use App\Models\Sue102;
use App\Models\Datoempr;
use App\Models\LiquidacionCorreccion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LiquidacionIndividualController extends Controller
{
    public function index(Request $request)
    {
        // Corrige conceptos con tipo numérico legacy según los Rangos de conceptos (sue089s)
        // antes de armar el recibo, para que la clasificación por tipo sea correcta.
        $ajustesTipos = Sue102::normalizarTiposNumericos();

        $empresa  = Datoempr::first();
        $periodos = Sue100::orderBy('periodo', 'desc')->orderBy('tipoliq', 'asc')->get(['id', 'periodo', 'tipoliq']);
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

        // Si no hay legajo seleccionado, usar el primero encontrado
        if (!$legajoId && $legajos->count() > 0) {
            $legajoDefault = $legajos->first();
            $legajoId = $legajoDefault->id;
            $periodoStr = $periodoStr ?: ($periodoActual ?? '');
            $tipoliq = $tipoliq ?? $tipoliqActual;
        }

        if ($legajoId) {
            $empleado = Sue001::with(['sector', 'jerarquia', 'ccosto', 'convenios', 'obraSijp', 'grupoEmpresario'])->find($legajoId);
        }

        if ($empleado && $periodoStr) {
            $conceptos = $this->cargarConceptos($empleado, $periodoStr, $tipoliq);
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
            'ajustesTipos' => $ajustesTipos,
            'historial'  => $this->cargarHistorial($empleado, $periodoStr, $tipoliq),
        ]);
    }

    /**
     * Carga los conceptos liquidados de un empleado/período/tipoliq, clasificados por tipo
     * (H=Haberes, D=Retenciones, AS=Asignaciones, resto=No Remunerativo). Incluye el id de sue090s
     * y el tipo resuelto para permitir edición inline.
     */
    private function cargarConceptos($empleado, string $periodoStr, $tipoliq): array
    {
        $rows = DB::table('sue090s')
            ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
            ->where('sue090s.legajo', $empleado->codigo)
            ->where('sue090s.periodo', $periodoStr)
            ->when($tipoliq !== null, function ($query) use ($tipoliq) {
                $query->where('sue090s.tipoliq', $tipoliq);
            })
            ->orderByRaw('CAST(sue090s.concepto AS UNSIGNED)')
            ->select(
                'sue090s.id              as id',
                'sue102s.id              as concepto_id',
                'sue090s.concepto        as codigo',
                'sue102s.detalle         as detalle',
                'sue090s.descripcion     as descripcion',
                'sue090s.cantidad',
                'sue090s.valor',
                'sue102s.tipo',
                'sue090s.importe',
                'sue090s.tiporem',
            )
            ->get();

        $conceptos = [];
        foreach ($rows as $row) {
            $tipo    = $row->tipo ?? '';
            $importe = abs($row->importe ?? 0);

            // Si el concepto no trae tipo en sue102s, se resuelve por rango en sue089s.
            if (!$tipo) {
                $tope = DB::table('sue089s')
                    ->where('desde', '<=', $row->codigo)
                    ->where('hasta', '>=', $row->codigo)
                    ->first();
                if ($tope) {
                    $tipo = $tope->tiporem;
                }
            }

            $haberes = null;
            $retenciones = null;
            $asignaciones = null;
            $noRemunerativo = null;

            if ($tipo === 'H') {
                $haberes = $importe;
            } elseif ($tipo === 'D') {
                $retenciones = $importe;
            } elseif ($tipo === 'AS') {
                $asignaciones = $importe;
            } else {
                $noRemunerativo = $importe;
            }

            $conceptos[] = [
                'id'              => $row->id,
                'concepto_id'     => $row->concepto_id,
                'codigo'          => $row->codigo,
                'tipo'            => $tipo,
                'detalle'         => $row->detalle ?? $row->descripcion ?? "Concepto {$row->codigo}",
                'cantidad'        => $row->cantidad,
                'valores'         => $row->valor,
                'importe'         => $importe,
                'haberes'         => $haberes,
                'retenciones'     => $retenciones,
                'asignaciones'    => $asignaciones,
                'no_remunerativo' => $noRemunerativo,
            ];
        }

        return $conceptos;
    }

    /**
     * Histórico de correcciones/ajustes de la liquidación de ese empleado en ese período (todos los orígenes).
     */
    private function cargarHistorial($empleado, ?string $periodoStr, $tipoliq): array
    {
        if (!$empleado || !$periodoStr) {
            return [];
        }

        $usuarios = User::pluck('name', 'id');

        return LiquidacionCorreccion::where('legajo', (string) $empleado->codigo)
            ->where('periodo', $periodoStr)
            ->when($tipoliq !== null, fn($q) => $q->where('tipoliq', $tipoliq))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'fecha' => optional($c->created_at)->format('Y-m-d H:i:s'),
                'concepto' => $c->concepto,
                'concepto_arca' => $c->concepto_arca,
                'importe_anterior' => (float) $c->importe_anterior,
                'importe_nuevo' => (float) $c->importe_nuevo,
                'diferencia' => round((float) $c->importe_nuevo - (float) $c->importe_anterior, 2),
                'motivo' => $c->motivo,
                'origen' => $c->origen,
                'usuario' => $c->usuario_id ? ($usuarios[$c->usuario_id] ?? ('#' . $c->usuario_id)) : '',
            ])
            ->values()
            ->all();
    }

    /**
     * Actualiza un campo (cantidad / valor / importe) de un concepto de la liquidación individual,
     * y asienta el cambio en el histórico de correcciones.
     */
    public function actualizarConcepto(Request $request)
    {
        $request->validate([
            'legajo_codigo' => 'required',
            'periodo'       => 'required',
            'tipoliq'       => 'required',
            'concepto'      => 'required',
            'campo'         => 'required|in:cantidad,valor,importe',
            'valor'         => 'required|numeric',
        ]);

        $fila = DB::table('sue090s')
            ->where('legajo', $request->legajo_codigo)
            ->where('periodo', $request->periodo)
            ->where('tipoliq', $request->tipoliq)
            ->where('concepto', $request->concepto)
            ->select('id', 'cantidad', 'valor', 'importe')
            ->first();

        if (!$fila) {
            return response()->json(['success' => false, 'message' => 'No se encontró el concepto en la liquidación.'], 404);
        }

        $campo = $request->campo;
        $valorIngresado = (float) $request->valor;
        $anterior = (float) ($fila->{$campo} ?? 0);

        // El importe se guarda preservando el signo original (descuentos/haberes negativos).
        if ($campo === 'importe') {
            $signo = ($anterior < 0) ? -1 : 1;
            $nuevo = $signo * abs($valorIngresado);
        } else {
            $nuevo = $valorIngresado;
        }

        DB::table('sue090s')->where('id', $fila->id)->update([$campo => $nuevo]);

        // Datos del legajo y del concepto para el histórico.
        $legajo = Sue001::where('codigo', $request->legajo_codigo)->first(['cuil']);
        $conceptoArca = DB::table('sue102s')->where('codigo', $request->concepto)->value('concepto_arca');

        $etiquetas = ['cantidad' => 'Cantidad', 'valor' => 'Valor', 'importe' => 'Importe'];
        $fmt = fn($v) => number_format((float) $v, 2, ',', '.');

        LiquidacionCorreccion::registrar([
            'periodo'          => $request->periodo,
            'tipoliq'          => (int) $request->tipoliq,
            'legajo'           => (string) $request->legajo_codigo,
            'cuil'             => $legajo->cuil ?? null,
            'concepto'         => (string) $request->concepto,
            'concepto_arca'    => $conceptoArca,
            'sue090_id'        => $fila->id,
            'importe_anterior' => $anterior,
            'importe_nuevo'    => $nuevo,
            'motivo'           => ($etiquetas[$campo] ?? $campo) . ": {$fmt($anterior)} → {$fmt($nuevo)} (edición liquidación individual)",
            'origen'           => 'liquidacion_individual',
        ]);

        return response()->json([
            'success' => true,
            'campo'   => $campo,
            'valor'   => $nuevo,
            'message' => 'Concepto actualizado.',
        ]);
    }

    public function eliminar(Request $request)
    {
        $request->validate([
            'legajo_codigo' => 'required',
            'periodo' => 'required',
            'tipoliq' => 'required',
        ]);

        $eliminados = DB::table('sue090s')
            ->where('legajo', $request->legajo_codigo)
            ->where('periodo', $request->periodo)
            ->where('tipoliq', $request->tipoliq)
            ->delete();

        return response()->json([
            'success' => true,
            'eliminados' => $eliminados,
            'message' => "Se eliminaron {$eliminados} conceptos.",
        ]);
    }
}
