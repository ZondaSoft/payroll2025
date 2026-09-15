<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorBaseArca extends ModeloMotor
{
    protected $table = 'motor_bases_arca';

    protected $primaryKey = 'id_base';

    protected $casts = [
        'admite_tope' => 'boolean',
        'activo' => 'boolean',
    ];
}
