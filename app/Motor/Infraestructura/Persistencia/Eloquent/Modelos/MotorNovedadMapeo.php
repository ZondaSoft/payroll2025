<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorNovedadMapeo extends ModeloMotor
{
    protected $table = 'motor_novedad_mapeo';

    protected $primaryKey = 'id_mapeo';

    protected $casts = [
        'es_importe' => 'boolean',
        'activo' => 'boolean',
    ];
}
