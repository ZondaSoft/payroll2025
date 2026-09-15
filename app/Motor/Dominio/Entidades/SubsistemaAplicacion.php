<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\TipoAplicacion;

/** Combinación oficial válida subsistema × APORTE|CONTRIBUCION, con su columna del LSD. */
final readonly class SubsistemaAplicacion
{
    public function __construct(
        public int $id,
        public int $idSubsistema,
        public string $codigoSubsistema,
        public TipoAplicacion $tipoAplicacion,
        public ?string $codigoBase,
        public int $columnaLsd,
        public bool $activo,
        public EstadoValidacion $estadoValidacion,
    ) {}

    /** Clave estable para snapshots: SIPA|APORTE */
    public function clave(): string
    {
        return $this->codigoSubsistema.'|'.$this->tipoAplicacion->value;
    }
}
