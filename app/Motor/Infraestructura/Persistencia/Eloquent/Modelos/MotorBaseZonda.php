<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorBaseZonda extends ModeloMotor
{
    protected $table = 'motor_bases_zonda';

    protected $primaryKey = 'id_base_zonda';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
