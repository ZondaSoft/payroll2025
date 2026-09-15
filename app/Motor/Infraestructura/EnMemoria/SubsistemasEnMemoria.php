<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Dominio\Entidades\Subsistema;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;

final class SubsistemasEnMemoria implements RepositorioSubsistemas
{
    /** @var list<Subsistema> */
    public array $subsistemas = [];

    /** @var list<SubsistemaAplicacion> */
    public array $aplicaciones = [];

    public function todos(): array
    {
        return $this->subsistemas;
    }

    public function aplicaciones(): array
    {
        return $this->aplicaciones;
    }
}
