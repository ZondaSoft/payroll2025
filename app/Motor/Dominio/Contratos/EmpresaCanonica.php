<?php

namespace App\Motor\Dominio\Contratos;

/** Empleador tal como lo entrega el adaptador de sue086s. */
final readonly class EmpresaCanonica
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $razonSocial,
        public ?string $cuit = null,
    ) {}
}
