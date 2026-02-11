<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportConceptosArcaOk extends Model
{
    protected $fillable = [
        'registro',
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
}
