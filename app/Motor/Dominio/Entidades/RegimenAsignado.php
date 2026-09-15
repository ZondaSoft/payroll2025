<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class RegimenAsignado
{
    public function __construct(
        public ?int $id,
        public int $idRegimen,
        public string $codigoRegimen,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
        public ?string $documentoRespaldo = null,
    ) {}
}
