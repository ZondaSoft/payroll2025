<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorEncuadramientoPatronal extends ModeloMotor
{
    protected $table = 'motor_encuadramientos_patronales';

    protected $primaryKey = 'encuadramiento';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
