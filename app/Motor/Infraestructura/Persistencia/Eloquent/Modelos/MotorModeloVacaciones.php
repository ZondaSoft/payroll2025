<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorModeloVacaciones extends ModeloMotor
{
    protected $table = 'motor_modelos_vacaciones';

    protected $primaryKey = 'id_modelo_vacaciones';

    protected $casts = [
        'activo' => 'boolean',
    ];
}
