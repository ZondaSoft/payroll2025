<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorParametro extends ModeloMotor
{
    protected $table = 'motor_parametros';

    protected $primaryKey = 'id_parametro';

    protected $casts = [
        'es_tecnico' => 'boolean',
        'activo' => 'boolean',
    ];
}
