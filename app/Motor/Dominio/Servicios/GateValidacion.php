<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;

/**
 * Regla del motor sobre estado_validacion (spec, todas las tablas normativas):
 *  - PRODUCCION: solo CONFIRMADO habilita; SIN_CONFIRMAR y SIN_DATO bloquean.
 *  - PRUEBA: CONFIRMADO habilita; SIN_CONFIRMAR habilita con advertencia explícita;
 *            SIN_DATO bloquea si el dato es obligatorio.
 */
final class GateValidacion
{
    public function __construct(public readonly Entorno $entorno) {}

    /**
     * Devuelve un Aviso si el dato se usa con advertencia, null si está habilitado sin más.
     *
     * @throws MotorBloqueado si el dato no está habilitado en este entorno
     */
    public function evaluar(EstadoValidacion $estado, string $entidad, string $descripcion, bool $obligatorio = true, ?string $concepto = null): ?Aviso
    {
        if ($estado === EstadoValidacion::Confirmado) {
            return null;
        }
        if ($estado === EstadoValidacion::SinDato) {
            if ($obligatorio) {
                throw MotorBloqueado::porque(CodigoBloqueo::EstadoNoHabilitado, "{$descripcion}: el dato está SIN_DATO y es obligatorio.", $entidad, 'estado_validacion');
            }

            return new Aviso('SIN_DATO', "{$descripcion}: sin dato (no obligatorio, se omite).", $entidad, $concepto);
        }
        // SIN_CONFIRMAR
        if ($this->entorno === Entorno::Produccion) {
            throw MotorBloqueado::porque(CodigoBloqueo::EstadoNoHabilitado, "{$descripcion}: está SIN_CONFIRMAR y en PRODUCCION solo se admite CONFIRMADO.", $entidad, 'estado_validacion');
        }

        return new Aviso('SIN_CONFIRMAR', "{$descripcion}: dato SIN_CONFIRMAR usado en entorno de PRUEBA.", $entidad, $concepto);
    }

    public function habilita(EstadoValidacion $estado, bool $obligatorio = true): bool
    {
        try {
            $this->evaluar($estado, '', '', $obligatorio);

            return true;
        } catch (MotorBloqueado) {
            return false;
        }
    }
}
