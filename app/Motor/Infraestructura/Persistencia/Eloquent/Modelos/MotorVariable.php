<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorVariable extends ModeloMotor
{
    protected $table = 'motor_variables';

    protected $primaryKey = 'codigo';

    protected $keyType = 'string';

    public $incrementing = false;
}
