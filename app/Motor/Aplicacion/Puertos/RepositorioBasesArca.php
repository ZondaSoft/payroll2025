<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\BaseArca;

/** Catálogo REM_1..REM_10. */
interface RepositorioBasesArca
{
    /** @return list<BaseArca> */
    public function todas(): array;

    public function porCodigo(string $codigo): ?BaseArca;
}
