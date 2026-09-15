<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioBasesArca;
use App\Motor\Dominio\Entidades\BaseArca;

final class BasesArcaEnMemoria implements RepositorioBasesArca
{
    /** @var list<BaseArca> */
    public array $bases = [];

    public function todas(): array
    {
        return $this->bases;
    }

    public function porCodigo(string $codigo): ?BaseArca
    {
        foreach ($this->bases as $b) {
            if ($b->codigo === $codigo) {
                return $b;
            }
        }

        return null;
    }
}
