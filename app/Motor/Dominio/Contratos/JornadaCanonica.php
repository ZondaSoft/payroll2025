<?php

namespace App\Motor\Dominio\Contratos;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Jornada tal como la entrega el adaptador de sue010s. En el legacy 159/205
 * activos no tienen jornada: sinDato() lo representa y el motor cae a
 * HORAS_MES_REFERENCIA con aviso.
 */
final readonly class JornadaCanonica
{
    public function __construct(
        public ?int $id,
        public string $descripcion,
        public ?int $horasSemanales,
        public bool $parcial,
        public bool $sinDato = false,
    ) {}

    public static function sinDato(): self
    {
        return new self(null, 'Jornada no informada', null, false, true);
    }

    /** Horas mensuales de referencia: semanales × 52 / 12 (48 → 208; 44 → 190,67). */
    public function horasMensuales(): ?BigDecimal
    {
        if ($this->horasSemanales === null || $this->horasSemanales <= 0) {
            return null;
        }

        return BigDecimal::of($this->horasSemanales)->multipliedBy(52)->dividedBy(12, 6, RoundingMode::HALF_UP);
    }
}
