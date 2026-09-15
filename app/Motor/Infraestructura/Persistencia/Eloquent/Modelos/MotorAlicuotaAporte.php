<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorAlicuotaAporte extends ModeloMotor
{
    protected $table = 'motor_alicuotas_aportes';

    protected $primaryKey = 'id_alicuota_aporte';

    protected $casts = [
        'aplica_tope' => 'boolean',
    ];
}
