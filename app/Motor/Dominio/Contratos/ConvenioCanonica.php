<?php

namespace App\Motor\Dominio\Contratos;

/** Convenio colectivo tal como lo entrega el adaptador de sue007s. */
final readonly class ConvenioCanonica
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public ?int $horasSemanales = null,
    ) {}
}
