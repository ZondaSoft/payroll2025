<?php

namespace App\Http\Controllers;

use App\Models\LiquidacionCorreccion;
use App\Models\Sue001;
use App\Models\Sue086;
use App\Models\User;
use Inertia\Inertia;

class LiquidacionCorreccionesController extends Controller
{
    /**
     * Visor del histórico de correcciones/ajustes automáticos sobre la liquidación.
     * Las correcciones son poco frecuentes (solo cuando se usa "Ajustar valores"), por eso se envían
     * todas y el filtrado/exportación se resuelven en el cliente.
     */
    public function index()
    {
        $correcciones = LiquidacionCorreccion::orderByDesc('created_at')->get();

        // Mapas para enriquecer sin N+1: empresa (vía legajo+cuil → grupo_emp) y nombre de usuario.
        $empresasPorCodigo = Sue086::pluck('detalle', 'codigo');
        $usuariosPorId = User::pluck('name', 'id');
        $cuils = $correcciones->pluck('cuil')->filter()->unique()->values();
        $legCuil = $cuils->isEmpty()
            ? collect()
            : Sue001::whereIn('cuil', $cuils)->get(['codigo', 'cuil', 'grupo_emp'])
                ->keyBy(fn($e) => $e->codigo . '|' . $e->cuil);

        $data = $correcciones->map(function ($c) use ($empresasPorCodigo, $usuariosPorId, $legCuil) {
            $ge = $legCuil[$c->legajo . '|' . $c->cuil]->grupo_emp ?? null;
            return [
                'id' => $c->id,
                'fecha' => optional($c->created_at)->format('Y-m-d H:i:s'),
                'periodo' => $c->periodo,
                'empresa' => $ge !== null ? ($empresasPorCodigo[$ge] ?? (string) $ge) : '',
                'legajo' => $c->legajo,
                'cuil' => $c->cuil,
                'concepto' => $c->concepto,
                'concepto_arca' => $c->concepto_arca,
                'importe_anterior' => (float) $c->importe_anterior,
                'importe_nuevo' => (float) $c->importe_nuevo,
                'diferencia' => round((float) $c->importe_nuevo - (float) $c->importe_anterior, 2),
                'motivo' => $c->motivo,
                'origen' => $c->origen,
                'usuario' => $c->usuario_id ? ($usuariosPorId[$c->usuario_id] ?? ('#' . $c->usuario_id)) : '',
            ];
        })->values();

        return Inertia::render('Liquidacion/Correcciones', [
            'correcciones' => $data,
            'periodos' => $correcciones->pluck('periodo')->filter()->unique()->sortDesc()->values(),
        ]);
    }
}
