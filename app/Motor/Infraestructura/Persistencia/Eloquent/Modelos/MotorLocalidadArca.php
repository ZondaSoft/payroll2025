<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorLocalidadArca extends ModeloMotor
{
    protected $table = 'motor_localidades_arca';

    protected $primaryKey = 'codigo_localidad_arca';

    public $incrementing = false;

    protected $keyType = 'string';
}
