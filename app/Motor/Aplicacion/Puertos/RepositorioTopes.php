<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\ValorObjetos\Periodo;

/** Topes por período (regla B: si no hay registro, el motor bloquea; nunca arrastra). */
interface RepositorioTopes
{
    /** @return list<Tope> */
    public function delPeriodo(Periodo $periodo): array;
}
