<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorCondicionArca extends ModeloMotor
{
    protected $table = 'motor_condiciones_arca';

    protected $primaryKey = 'codigo_condicion';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
