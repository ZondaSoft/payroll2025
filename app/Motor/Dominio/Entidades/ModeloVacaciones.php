<?php

namespace App\Motor\Dominio\Entidades;

final readonly class ModeloVacaciones
{
    /** @param list<TramoVacaciones> $tramos */
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public array $tramos,
        public bool $activo = true,
    ) {}
}
