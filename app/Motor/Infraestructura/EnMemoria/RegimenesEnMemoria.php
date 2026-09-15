<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Dominio\Entidades\RegimenContributivo;
use DateTimeInterface;

final class RegimenesEnMemoria implements RepositorioRegimenes
{
    use SecuenciaEnMemoria;

    /** @var list<RegimenContributivo> */
    public array $items = [];

    public function vigentes(DateTimeInterface $fecha): array
    {
        return $this->vigentesA_($this->items, $fecha);
    }

    public function todos(): array
    {
        return $this->items;
    }

    public function porCodigo(string $codigo): ?RegimenContributivo
    {
        foreach ($this->items as $r) {
            if ($r->codigo === $codigo) {
                return $r;
            }
        }

        return null;
    }
}
