<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class Basico
{
    public function __construct(
        public ?int $id,
        public int $idCategoria,
        public ?string $codigoCategoria,
        public Vigencia $vigencia,
        public Importe $importe,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
