<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\ValorObjetos\Porcentaje;
use Brick\Math\BigDecimal;

final readonly class TramoAntiguedad
{
    public function __construct(
        public BigDecimal $aniosDesde,
        public ?BigDecimal $aniosHasta,
        public Porcentaje $porcentaje,
        public int $orden,
    ) {}

    public function contiene(int $anios): bool
    {
        $a = BigDecimal::of($anios);

        return $a->isGreaterThanOrEqualTo($this->aniosDesde) && ($this->aniosHasta === null || $a->isLessThanOrEqualTo($this->aniosHasta));
    }
}
