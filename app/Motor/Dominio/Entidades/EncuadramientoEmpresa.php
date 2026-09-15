<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class EncuadramientoEmpresa
{
    public function __construct(
        public ?int $id,
        public int $empresaId,
        public string $encuadramiento,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
        public ?string $detalle = null,
    ) {}
}
