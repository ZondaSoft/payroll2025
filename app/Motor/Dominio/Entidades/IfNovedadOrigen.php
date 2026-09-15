<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoIfNovedad;
use DateTimeImmutable;

/** Procedencia de una novedad normalizada, o registro de por qué no pudo normalizarse. */
final readonly class IfNovedadOrigen
{
    public function __construct(
        public ?int $id,
        public ?int $idNovedad,
        public string $sistemaOrigen,
        public string $tablaOrigen,
        public string $claveOrigen,
        public DateTimeImmutable $fechaImportacion,
        public ?string $hashOrigen,
        public EstadoIfNovedad $estado,
        public ?string $motivo = null,
    ) {}
}
