<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue007 extends Model
{
    protected $table = 'sue007s';

    protected $fillable = [
        'codigo',
        'detalle',
        'hs_normales_diarias',
        'hs_normales_semanales',
        'bh_habilitado',
        'bh_tope_saldo_positivo',
        'bh_meses_vencimiento',
        'bh_al_vencer',
        'bh_convierte_a_extra_pct',
        'bh_cod_nov_franco',
        'bh_cod_nov_paga_extra',
        'je_habilitada',
        'je_hs_normales',
        'je_hs_dobles',
        'je_cod_nov_doble',
        'noct_100',
        'forzar50',
        'porc_tarea_dif',
    ];

    protected $casts = [
        'bh_habilitado'            => 'boolean',
        'je_habilitada'            => 'boolean',
        'noct_100'                 => 'boolean',
        'hs_normales_diarias'      => 'integer',
        'hs_normales_semanales'    => 'integer',
        'bh_meses_vencimiento'     => 'integer',
        'bh_tope_saldo_positivo'   => 'decimal:2',
        'bh_convierte_a_extra_pct' => 'decimal:2',
        'je_hs_normales'           => 'decimal:2',
        'je_hs_dobles'             => 'decimal:2',
        'porc_tarea_dif'           => 'decimal:2',
    ];
}
