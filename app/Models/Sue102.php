<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sue102 extends Model
{
    protected $fillable = [
        'codigo',
        'detalle',
        'tipo',
        'formula',
        'porcentaje',
        'importe_fijo',
        'imponible',
        'afecta_sac',
        'afecta_vacaciones',
        'imprime_recibo',
        'orden_impresion',
        'activo',
        'cuenta_contable',
        'observaciones',
        'sicoss_afecta',
        'gcias_afecta',
        'concepto_arca',
    ];

    protected $casts = [
        'imponible' => 'boolean',
        'afecta_sac' => 'boolean',
        'afecta_vacaciones' => 'boolean',
        'imprime_recibo' => 'boolean',
        'activo' => 'boolean',
        'sicoss_afecta' => 'boolean',
        'gcias_afecta' => 'boolean',
    ];

    const TIPOS = [
        1 => 'HABER',
        2 => 'DESCUENTO',
        3 => 'ASIGNACIONES',
        4 => 'NO_REMUNERATIVO',
        5 => 'GANANCIAS',
        6 => 'DEVOLUCIÓN DE GANANCIA',
        7 => 'REDONDEO',
        8 => 'APORTES',
        9 => 'AUXILIARES',
    ];

    public function getTipoNombreAttribute()
    {
        return self::TIPOS[$this->tipo] ?? 'Desconocido';
    }

    /**
     * Corrige "on the fly" los conceptos cuyo `tipo` quedó en formato numérico legacy
     * (1..9) en vez de letra, asignándoles el tiporem del rango de sue089s
     * (Configuración > Rangos de conceptos) que contiene su código.
     *
     * Es idempotente: tras corregir, no quedan tipos numéricos y devuelve [].
     *
     * @return array<int, array{codigo:mixed, detalle:?string, anterior:string, nuevo:string}>
     *         Lista de ajustes realizados (vacía si no hubo ninguno).
     */
    public static function normalizarTiposNumericos(): array
    {
        // Conceptos con tipo en formato numérico (no letra)
        $numericos = static::whereRaw("tipo REGEXP '^[0-9]+$'")
            ->get(['id', 'codigo', 'detalle', 'tipo']);

        if ($numericos->isEmpty()) {
            return [];
        }

        $rangos = DB::table('sue089s')->get();

        $tipoPorCodigo = function ($codigo) use ($rangos): ?string {
            foreach ($rangos as $r) {
                if ($codigo >= $r->desde && $codigo <= $r->hasta) {
                    $t = trim($r->tiporem ?? '');
                    return $t !== '' ? $t : null;
                }
            }
            return null;
        };

        $ajustes = [];
        foreach ($numericos as $c) {
            $nuevo = $tipoPorCodigo($c->codigo);

            // Sin rango que lo cubra → no se puede corregir, se deja como está
            if ($nuevo === null || $nuevo === $c->tipo) {
                continue;
            }

            static::where('id', $c->id)->update(['tipo' => $nuevo]);

            $ajustes[] = [
                'codigo'   => $c->codigo,
                'detalle'  => $c->detalle,
                'anterior' => $c->tipo,
                'nuevo'    => $nuevo,
            ];
        }

        return $ajustes;
    }
}
