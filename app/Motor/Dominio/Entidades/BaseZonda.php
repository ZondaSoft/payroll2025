<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;

final readonly class BaseZonda
{
    public function __construct(
        public ?int $id,
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
        public bool $activo,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
