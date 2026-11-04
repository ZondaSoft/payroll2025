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
        /** GENERAL: se evalúa para toda relación. REGIMEN: sólo cuando un régimen de la relación la habilita (DBML v2). */
        public string $aplicabilidad = self::GENERAL,
    ) {}

    public const GENERAL = 'GENERAL';

    public const REGIMEN = 'REGIMEN';

    public function esPorRegimen(): bool
    {
        return $this->aplicabilidad === self::REGIMEN;
    }

    /** Clave estable para snapshots: SIPA|APORTE */
    public function clave(): string
    {
        return $this->codigoSubsistema.'|'.$this->tipoAplicacion->value;
    }
}
