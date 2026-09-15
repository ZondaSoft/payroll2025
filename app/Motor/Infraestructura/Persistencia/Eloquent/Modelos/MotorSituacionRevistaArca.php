<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorSituacionRevistaArca extends ModeloMotor
{
    protected $table = 'motor_situaciones_revista_arca';

    protected $primaryKey = 'codigo_situacion';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
