<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorSubsistema extends ModeloMotor
{
    protected $table = 'motor_subsistemas';

    protected $primaryKey = 'id_subsistema';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
