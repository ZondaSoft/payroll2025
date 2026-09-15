<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorComponenteTope extends ModeloMotor
{
    protected $table = 'motor_componentes_tope';

    protected $primaryKey = 'codigo_componente';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
