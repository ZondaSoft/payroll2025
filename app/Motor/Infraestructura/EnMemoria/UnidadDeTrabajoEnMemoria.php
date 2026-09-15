<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use Closure;

final class UnidadDeTrabajoEnMemoria implements UnidadDeTrabajo
{
    public function ejecutar(Closure $operacion): mixed
    {
        return $operacion();
    }
}
