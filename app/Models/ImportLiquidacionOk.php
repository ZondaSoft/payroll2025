<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLiquidacionOk extends Model
{
    use HasFactory;

    protected $fillable = [
        'registro',
        'periodo',
        'legajo',
        'cuil',
        'descripcion',
        'importe',
        'detalle',
        'situacion_ant', 'situacion_imp',
        'obra_social_ant', 'obra_social_imp',
        'condicion_ant', 'condicion_imp',
        'actividad_ant', 'actividad_imp',
        'modalidad_ant', 'modalidad_imp',
        'siniestro_ant', 'siniestro_imp',
        'localidad_ant', 'localidad_imp',
    ];
}
