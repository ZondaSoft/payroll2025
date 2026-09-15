<?php

namespace App\Motor\Dominio\Entidades;

/** Vínculo entre la relación laboral canónica y su registro de origen (sue001s.id). */
final readonly class IfRelacionOrigen
{
    public function __construct(
        public ?int $id,
        public int $relacionLaboralId,
        public string $sistemaOrigen,
        public string $tablaOrigen,
        public string $claveOrigen,
        public ?string $codigoLegajoOrigen,
        public ?string $hashOrigen,
        public bool $activo = true,
    ) {}
}
