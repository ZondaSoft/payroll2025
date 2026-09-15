<?php

namespace App\Motor\Dominio\Contratos;

use DateTimeImmutable;

/**
 * Legajo tal como lo entrega el adaptador de sue001s: solo lo necesario para
 * crear/sincronizar la relación laboral canónica (no las 173 columnas).
 */
final readonly class LegajoCanonica
{
    public function __construct(
        public int $id,
        public int $codigo,
        public ?string $cuil,
        public string $apellidoNombre,
        public ?DateTimeImmutable $fechaIngreso,
        public ?DateTimeImmutable $fechaEgreso,
        public ?DateTimeImmutable $fechaAntiguedad,
        public ?string $codigoCategoria,
        public ?string $codigoConvenio,
        public ?int $jornadaId,
        public ?string $codigoEmpresa,
        public ?string $codigoSindicato,
        public ?string $sicossCondicion,
        public ?string $sicossActividad,
        public ?string $sicossModalidad,
        public ?string $sicossSituacion,
        public ?string $sicossLocalidad,
        public ?string $bruto,
    ) {}

    public function estaActivo(): bool
    {
        return $this->fechaEgreso === null;
    }

    /** Huella de los campos que, si cambian en el legacy, obligan a resincronizar. */
    public function hash(): string
    {
        return hash('sha256', implode('|', [
            $this->codigo, $this->cuil, $this->apellidoNombre,
            $this->fechaIngreso?->format('Y-m-d'), $this->fechaEgreso?->format('Y-m-d'), $this->fechaAntiguedad?->format('Y-m-d'),
            $this->codigoCategoria, $this->codigoConvenio, $this->jornadaId, $this->codigoEmpresa, $this->codigoSindicato,
            $this->sicossCondicion, $this->sicossActividad, $this->sicossModalidad, $this->sicossSituacion, $this->sicossLocalidad,
        ]));
    }
}
