<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;

final readonly class Tope
{
    public function __construct(
        public int $id,
        public string $codigo,
        public Periodo $periodo,
        public string $codigoBase,
        public TipoTope $tipoTope,
        public ComponenteTope $componente,
        /** NULL = SIN_DATO (regla C: bloquea, nunca es cero) */
        public ?Importe $importe,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
