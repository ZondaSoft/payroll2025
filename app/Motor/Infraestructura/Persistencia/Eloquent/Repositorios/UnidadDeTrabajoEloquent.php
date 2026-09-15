<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use Closure;
use Illuminate\Support\Facades\DB;

final class UnidadDeTrabajoEloquent implements UnidadDeTrabajo
{
    public function ejecutar(Closure $operacion): mixed
    {
        return DB::transaction($operacion);
    }
}
