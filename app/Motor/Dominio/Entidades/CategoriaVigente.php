<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class CategoriaVigente
{
    public function __construct(
        public ?int $id,
        public ?int $idCategoria,
        public ?string $codigoCategoria,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
