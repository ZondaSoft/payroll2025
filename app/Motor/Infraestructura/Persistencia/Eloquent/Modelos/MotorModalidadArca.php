<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorModalidadArca extends ModeloMotor
{
    protected $table = 'motor_modalidades_arca';

    protected $primaryKey = 'codigo_modalidad';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
