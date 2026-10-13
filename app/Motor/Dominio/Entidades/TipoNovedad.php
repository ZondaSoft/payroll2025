<?php

namespace App\Motor\Dominio\Entidades;

final readonly class TipoNovedad
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        /** NULL cuando el origen la declara PENDIENTE_UNIDAD: el motor bloquea antes de suponerla. */
        public ?string $unidad,
        public bool $activo = true,
    ) {}
}
