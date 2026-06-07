<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue086 extends Model
{
    protected $fillable = [
        'codigo',
        'detalle',
        'fantasia',
        'cuit',
        'direccion_comercial',
        'localidad_comercial',
        'cod_pos_comercial',
        'direccion_fiscal',
        'localidad_fiscal',
        'cod_pos_fiscal',
        'telefono',
        'email',
        'web',
        'tipo',
        'actividad',
        'tipo_empleador_lsd',
        'nom_arch',
        'legajo_desde',
        'legajo_hasta',
    ];

    protected $casts = [
        'legajo_desde' => 'integer',
        'legajo_hasta' => 'integer',
    ];

    protected $attributes = [
        'tipo_empleador_lsd' => '1',
    ];

    public const TIPOS_EMPLEADOR_LSD = [
        '0' => 'Administración Pública',
        '1' => 'Decreto 814/01, Art 2, Inc. B',
        '2' => 'Servicios Eventuales, Art 2, Inc. B',
        '4' => 'Decreto 814/01, Art 2, Inc. A',
        '5' => 'Servicios Eventuales, Art 2, Inc. A',
        '7' => 'Enseñanza Privada',
        '8' => 'Decreto 1212/03 - AFA Clubes',
    ];
}
