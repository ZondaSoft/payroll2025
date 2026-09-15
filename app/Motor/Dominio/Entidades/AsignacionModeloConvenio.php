<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/** Asigna a un convenio (legacy) un modelo de antigüedad o de vacaciones, con vigencia. */
final readonly class AsignacionModeloConvenio
{
    public function __construct(
        public ?int $id,
        public int $idConvenio,
        public int $idModelo,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
