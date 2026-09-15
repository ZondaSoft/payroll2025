<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\ModeloVacaciones;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use Brick\Math\BigDecimal;

/** Días de vacaciones según la escala del convenio (tramos con límites inclusivos/exclusivos). */
final class ResolutorVacaciones
{
    public function dias(ModeloVacaciones $modelo, BigDecimal $anios): int
    {
        foreach ($modelo->tramos as $t) {
            if ($t->contiene($anios)) {
                return $t->dias;
            }
        }
        throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "El modelo de vacaciones {$modelo->codigo} no tiene tramo para {$anios} años.", 'motor_vacaciones_tramos');
    }
}
