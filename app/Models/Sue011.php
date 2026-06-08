<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue011 extends Model
{
    protected $table = 'sue011s';

    protected $fillable = [
        'codigo',
        'detalle',
        'tipo_horar',
        'color',
        'vacac_max_simultaneos',
        'vacac_tipo_dias',
        'cobertura_minima_por_turno',
        'cod_horar',
        'hijo_de',
    ];

    protected $casts = [
        'tipo_horar'            => 'integer',
        'vacac_max_simultaneos' => 'integer',
        'cobertura_minima_por_turno' => 'array',
    ];
}
