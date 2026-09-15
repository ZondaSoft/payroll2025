<?php

namespace App\Motor\Dominio\Excepciones;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use DomainException;

/**
 * Bloqueo del motor (fail-closed). Nunca se degrada a importe cero: quien la
 * captura (MotorLiquidacion) la acumula en el resultado con código, motivo,
 * entidad y campo para que la UI y la traza puedan explicarla.
 */
final class MotorBloqueado extends DomainException
{
    public function __construct(
        public readonly CodigoBloqueo $codigo,
        string $motivo,
        public readonly ?string $entidad = null,
        public readonly ?string $campo = null,
        public readonly bool $fatal = false,
    ) {
        parent::__construct($motivo);
    }

    public static function porque(CodigoBloqueo $codigo, string $motivo, ?string $entidad = null, ?string $campo = null): self
    {
        return new self($codigo, $motivo, $entidad, $campo, false);
    }

    /** Bloqueo que corta toda la liquidación (conflicto de perfiles, RL inconsistente, parámetro técnico ausente). */
    public static function fatal(CodigoBloqueo $codigo, string $motivo, ?string $entidad = null, ?string $campo = null): self
    {
        return new self($codigo, $motivo, $entidad, $campo, true);
    }

    /** @return array{codigo: string, motivo: string, entidad: ?string, campo: ?string, fatal: bool} */
    public function aArray(): array
    {
        return [
            'codigo' => $this->codigo->value,
            'motivo' => $this->getMessage(),
            'entidad' => $this->entidad,
            'campo' => $this->campo,
            'fatal' => $this->fatal,
        ];
    }
}
