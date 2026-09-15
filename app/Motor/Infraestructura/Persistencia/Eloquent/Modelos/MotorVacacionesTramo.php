<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorVacacionesTramo extends ModeloMotor
{
    protected $table = 'motor_vacaciones_tramos';

    protected $primaryKey = 'id_tramo_vacaciones';

    protected $casts = [
        'incluye_desde' => 'boolean',
        'incluye_hasta' => 'boolean',
    ];
}
