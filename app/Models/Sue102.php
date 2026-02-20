<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
