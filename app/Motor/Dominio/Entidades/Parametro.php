<?php

namespace App\Motor\Dominio\Entidades;

final readonly class Parametro
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public string $tipo,
        public ?string $unidad,
        public bool $esTecnico,
        public bool $activo,
    ) {}
}
