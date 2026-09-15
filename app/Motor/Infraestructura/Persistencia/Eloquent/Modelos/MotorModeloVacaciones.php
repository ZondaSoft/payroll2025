<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

class MotorModeloVacaciones extends ModeloMotor
{
    protected $table = 'motor_modelos_vacaciones';

    protected $primaryKey = 'id_modelo_vacaciones';

    public function tramos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MotorVacacionesTramo::class, 'id_modelo_vacaciones', 'id_modelo_vacaciones');
    }

    protected $casts = [
        'activo' => 'boolean',
    ];
}
