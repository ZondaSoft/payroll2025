<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorActividadArca extends ModeloMotor
{
    protected $table = 'motor_actividades_arca';

    protected $primaryKey = 'codigo_actividad_arca';

    public $incrementing = false;

    protected $keyType = 'string';
}
