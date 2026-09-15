<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorConcepto extends ModeloMotor
{
    protected $table = 'motor_conceptos';

    protected $primaryKey = 'id_concepto';

    protected $casts = [
        'requiere_novedad' => 'boolean',
        'editable_manual' => 'boolean',
        'repetible' => 'boolean',
        'permite_importe_cero' => 'boolean',
        'imprime_recibo' => 'boolean',
        'es_concepto_sistema' => 'boolean',
        'activo' => 'boolean',
        'meses_aplicacion' => 'array',
        'tipos_liquidacion' => 'array',
    ];
}
