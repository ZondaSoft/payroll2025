<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorSubsistemaAplicacion extends ModeloMotor
{
    protected $table = 'motor_subsistema_aplicacion';

    protected $primaryKey = 'id_subsistema_aplicacion';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
