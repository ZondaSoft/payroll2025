<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorModeloAntiguedad extends ModeloMotor
{
    protected $table = 'motor_modelos_antiguedad';

    protected $primaryKey = 'id_modelo_antiguedad';

    public function tramos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MotorAntiguedadTramo::class, 'id_modelo_antiguedad', 'id_modelo_antiguedad');
    }

    protected $casts = [
        'activo' => 'boolean',
    ];
}
