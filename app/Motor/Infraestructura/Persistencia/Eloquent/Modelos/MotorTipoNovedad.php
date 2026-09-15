<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorTipoNovedad extends ModeloMotor
{
    protected $table = 'motor_tipos_novedad';

    protected $primaryKey = 'id_tipo_novedad';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
