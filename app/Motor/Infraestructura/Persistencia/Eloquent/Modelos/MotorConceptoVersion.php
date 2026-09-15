<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorConceptoVersion extends ModeloMotor
{
    protected $table = 'motor_concepto_versiones';

    protected $primaryKey = 'id_version';

    protected $casts = [
        'snapshot' => 'array',
        'usada_en_liquidacion' => 'boolean',
        'publicada_at' => 'datetime',
    ];
}
