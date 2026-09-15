<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Dominio\Enums\EstadoValidacion;
use InvalidArgumentException;

/**
 * Transiciones permitidas de estado_validacion (dimensión independiente de activo/vigencia):
 * SIN_DATO → SIN_CONFIRMAR → CONFIRMADO, y vuelta a SIN_CONFIRMAR si se observa.
 * La persistencia concreta la hace cada controlador de catálogo (columna homónima); acá vive solo la regla.
 */
final class CambiarEstadoValidacion
{
    public function validarTransicion(EstadoValidacion $actual, EstadoValidacion $nuevo, bool $tieneDato): EstadoValidacion
    {
        if ($nuevo !== EstadoValidacion::SinDato && ! $tieneDato) {
            throw new InvalidArgumentException('No se puede confirmar ni marcar como cargado un registro sin dato (importe/valor vacío).');
        }
        if ($actual === EstadoValidacion::SinDato && $nuevo === EstadoValidacion::Confirmado) {
            throw new InvalidArgumentException('Un dato SIN_DATO debe pasar primero por SIN_CONFIRMAR (cargar y revisar) antes de CONFIRMADO.');
        }

        return $nuevo;
    }
}
