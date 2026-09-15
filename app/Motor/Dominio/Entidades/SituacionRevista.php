<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\ValorObjetos\Periodo;

final readonly class SituacionRevista
{
    public function __construct(
        public ?int $id,
        public Periodo $periodo,
        public int $orden,
        public string $codigoSituacion,
        public int $diaDesde,
        public int $diaHasta,
        public ?string $origen = null,
    ) {}
}
