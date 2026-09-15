<?php

namespace App\Motor\Dominio\Entidades;

final readonly class Subsistema
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public string $moduloResponsable,
        public ?string $baseAportes,
        public string $baseContribuciones,
        public bool $activo = true,
    ) {}
}
