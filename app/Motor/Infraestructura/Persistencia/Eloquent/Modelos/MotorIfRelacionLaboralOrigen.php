<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorIfRelacionLaboralOrigen extends ModeloMotor
{
    protected $table = 'motor_if_relacion_laboral_origen';

    protected $primaryKey = 'id_interfaz_relacion';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
