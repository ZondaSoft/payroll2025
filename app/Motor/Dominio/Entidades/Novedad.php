<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Brick\Math\BigDecimal;

/** Novedad normalizada: o cantidad (unidades) o valor (importe), nunca ambiguo. */
final readonly class Novedad
{
    public function __construct(
        public ?int $id,
        public Periodo $periodo,
        public int $relacionLaboralId,
        public string $codigoNovedad,
        public ?BigDecimal $cantidad,
        public ?Importe $valor,
        public ?string $origen = null,
    ) {}
}
