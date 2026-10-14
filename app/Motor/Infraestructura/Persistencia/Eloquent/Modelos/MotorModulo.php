<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorModulo extends ModeloMotor
{
    protected $table = 'motor_modulos';

    protected $primaryKey = 'codigo';

    protected $keyType = 'string';

    public $incrementing = false;
}
