<?php

namespace App\Motor\Dominio\Contratos;

/** Categoría laboral tal como la entrega el adaptador de sue006s. */
final readonly class CategoriaCanonica
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public ?string $codigoConvenio = null,
    ) {}
}
