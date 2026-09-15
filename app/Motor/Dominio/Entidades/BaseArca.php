<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\SujetoObligado;

final readonly class BaseArca
{
    public function __construct(
        public string $codigo,
        public string $descripcion,
        public SujetoObligado $sujetoObligado,
        public bool $admiteTope,
        public bool $activo = true,
    ) {}
}
