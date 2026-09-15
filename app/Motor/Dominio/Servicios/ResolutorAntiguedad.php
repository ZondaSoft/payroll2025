<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\ModeloAntiguedad;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\MetodoAntiguedad;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\ValorObjetos\Porcentaje;

/**
 * Porcentaje de antigüedad según el modelo del convenio:
 *  ESCALA → tramo que contiene los años (tope porcentaje_maximo si existe)
 *  LINEAL → porcentaje_por_anio × años, sin tope si porcentaje_maximo es NULL.
 * El resultado siempre es un porcentaje (spec MODELOS_ANTIGUEDAD).
 */
final class ResolutorAntiguedad
{
    public function porcentaje(ModeloAntiguedad $modelo, int $anios): Porcentaje
    {
        $p = match ($modelo->metodo) {
            MetodoAntiguedad::Escala => $this->porEscala($modelo, $anios),
            MetodoAntiguedad::Lineal => $this->porLineal($modelo, $anios),
        };
        if ($modelo->porcentajeMaximo !== null && $p->fraccion()->isGreaterThan($modelo->porcentajeMaximo->fraccion())) {
            return $modelo->porcentajeMaximo;
        }

        return $p;
    }

    private function porEscala(ModeloAntiguedad $modelo, int $anios): Porcentaje
    {
        if ($modelo->tramos === []) {
            throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "El modelo de antigüedad {$modelo->codigo} es ESCALA pero no tiene tramos.", 'motor_antiguedad_tramos');
        }
        foreach ($modelo->tramos as $t) {
            if ($t->contiene($anios)) {
                return $t->porcentaje;
            }
        }
        throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "El modelo de antigüedad {$modelo->codigo} no tiene tramo para {$anios} años.", 'motor_antiguedad_tramos', 'anios_desde');
    }

    private function porLineal(ModeloAntiguedad $modelo, int $anios): Porcentaje
    {
        if ($modelo->porcentajePorAnio === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "El modelo de antigüedad {$modelo->codigo} es LINEAL pero no tiene porcentaje por año.", 'motor_modelos_antiguedad', 'porcentaje_por_anio');
        }

        return Porcentaje::deFraccion($modelo->porcentajePorAnio->fraccion()->multipliedBy($anios));
    }
}
