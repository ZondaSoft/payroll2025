<?php

namespace App\Motor\Aplicacion\Puertos;

use Closure;

/** Transacción atómica alrededor de un caso de uso que escribe. */
interface UnidadDeTrabajo
{
    public function ejecutar(Closure $operacion): mixed;
}
