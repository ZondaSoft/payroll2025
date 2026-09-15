<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\RegimenContributivo;
use DateTimeInterface;

/** Regímenes contributivos. */
interface RepositorioRegimenes
{
    /** @return list<RegimenContributivo> */
    public function vigentes(DateTimeInterface $fecha): array;

    /** @return list<RegimenContributivo> */
    public function todos(): array;

    public function porCodigo(string $codigo): ?RegimenContributivo;
}
