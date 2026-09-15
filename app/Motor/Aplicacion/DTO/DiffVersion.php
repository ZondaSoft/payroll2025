<?php

namespace App\Motor\Aplicacion\DTO;

/** Diferencias campo a campo entre dos snapshots de concepto (versión vigente vs borrador u otra versión). */
final readonly class DiffVersion
{
    /** @param list<array{ruta: string, antes: mixed, despues: mixed}> $cambios */
    public function __construct(
        public string $codigoConcepto,
        public ?int $versionBase,
        public ?int $versionComparada,
        public array $cambios,
        public bool $identicos,
    ) {}
}
