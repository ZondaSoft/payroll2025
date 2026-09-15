<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/** Encuadre SICOSS de la relación laboral (1:1): condición, actividad, modalidad, localidad. */
final readonly class EncuadreArca
{
    public function __construct(
        public ?int $id,
        public string $codigoCondicion,
        public ?string $actividadInterna,
        public ?string $codigoActividad,
        public string $codigoModalidad,
        public ?string $codigoLocalidad,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
