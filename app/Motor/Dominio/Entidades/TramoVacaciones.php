<?php

namespace App\Motor\Dominio\Entidades;

use Brick\Math\BigDecimal;

final readonly class TramoVacaciones
{
    public function __construct(
        public BigDecimal $aniosDesde,
        public bool $incluyeDesde,
        public ?BigDecimal $aniosHasta,
        public ?bool $incluyeHasta,
        public int $dias,
        public int $orden,
    ) {}

    public function contiene(BigDecimal $anios): bool
    {
        $desdeOk = $this->incluyeDesde ? $anios->isGreaterThanOrEqualTo($this->aniosDesde) : $anios->isGreaterThan($this->aniosDesde);
        if (! $desdeOk) {
            return false;
        }
        if ($this->aniosHasta === null) {
            return true;
        }

        return ($this->incluyeHasta ?? true) ? $anios->isLessThanOrEqualTo($this->aniosHasta) : $anios->isLessThan($this->aniosHasta);
    }
}
