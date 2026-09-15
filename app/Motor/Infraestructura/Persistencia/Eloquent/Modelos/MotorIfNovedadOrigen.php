<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorIfNovedadOrigen extends ModeloMotor
{
    protected $table = 'motor_if_novedad_origen';

    protected $primaryKey = 'id_interfaz_novedad';

    protected $casts = [
        'fecha_importacion' => 'datetime',
    ];
}
