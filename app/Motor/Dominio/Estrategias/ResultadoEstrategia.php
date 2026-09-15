<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\ValorObjetos\Importe;
use Brick\Math\BigDecimal;

final readonly class ResultadoEstrategia
{
    /** @param array<string, mixed> $detalle para la traza (fórmula aplicada, operandos) */
    public function __construct(
        public Importe $importe,
        public ?BigDecimal $cantidad = null,
        public ?Importe $valorUnitario = null,
        public array $detalle = [],
        public bool $manual = false,
    ) {}

    public static function manual(): self
    {
        return new self(Importe::cero(), null, null, ['manual' => true], true);
    }
}
