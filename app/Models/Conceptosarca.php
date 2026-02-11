<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conceptosarca extends Model
{
    protected $fillable = [
        'id_empresa',
        'codigo_afip',
        'descripcion',
        'codigo_contribuyente',
        'descripcion_contribuyente',
        'marca_repetible',
        'aportes_sipa',
        'contribuciones_sipa',
        'aportes_inssjyp',
        'contribuciones_inssjyp',
        'aportes_obra_social',
        'contribuciones_obra_social',
        'aportes_fsr',
        'contribuciones_fsr',
        'aportes_renatea',
        'contribuciones_renatea',
        'contribuciones_aaff',
        'contribuciones_fne',
        'contribuciones_lrt',
        'aportes_diferenciales',
        'aportes_especiales',
    ];

    protected $casts = [
        'codigo_afip' => 'integer',
        'codigo_contribuyente' => 'integer',
        'marca_repetible' => 'decimal:3',
        'aportes_sipa' => 'decimal:3',
        'contribuciones_sipa' => 'decimal:3',
        'aportes_inssjyp' => 'decimal:3',
        'contribuciones_inssjyp' => 'decimal:3',
        'aportes_obra_social' => 'decimal:3',
        'contribuciones_obra_social' => 'decimal:3',
        'aportes_fsr' => 'decimal:3',
        'contribuciones_fsr' => 'decimal:3',
        'aportes_renatea' => 'decimal:3',
        'contribuciones_renatea' => 'decimal:3',
        'contribuciones_aaff' => 'decimal:3',
        'contribuciones_fne' => 'decimal:3',
        'contribuciones_lrt' => 'decimal:3',
        'aportes_diferenciales' => 'decimal:3',
        'aportes_especiales' => 'decimal:3',
    ];
}
