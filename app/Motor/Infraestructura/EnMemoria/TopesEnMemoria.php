<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioTopes;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\ValorObjetos\Periodo;

final class TopesEnMemoria implements RepositorioTopes
{
    /** @var list<Tope> */
    public array $items = [];

    public function delPeriodo(Periodo $periodo): array
    {
        return array_values(array_filter($this->items, fn ($t) => $t->periodo->igualA($periodo)));
    }
}
