<?php

namespace App\Motor\Dominio\Entidades;

final readonly class TipoNovedad
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public string $unidad,
        public bool $activo = true,
    ) {}
}
