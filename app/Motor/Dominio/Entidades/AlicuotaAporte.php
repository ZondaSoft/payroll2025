<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class AlicuotaAporte
{
    public function __construct(
        public int $id,
        public string $codigo,
        public int $idSubsistema,
        public string $codigoSubsistema,
        /** NULL = comodín: aplica a toda condición sin fila específica */
        public ?string $codigoCondicion,
        public Porcentaje $porcentaje,
        public string $baseAplicable,
        public bool $aplicaTope,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}

    public function esComodin(): bool
    {
        return $this->codigoCondicion === null;
    }
}
