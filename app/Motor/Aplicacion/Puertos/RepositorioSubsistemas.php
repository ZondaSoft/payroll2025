<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Subsistema;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;

/** Subsistemas y sus aplicaciones oficiales. */
interface RepositorioSubsistemas
{
    /** @return list<Subsistema> */
    public function todos(): array;

    /** @return list<SubsistemaAplicacion> */
    public function aplicaciones(): array;
}
