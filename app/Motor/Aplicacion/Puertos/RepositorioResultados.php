<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;

/** Persistencia de resultados: fase futura (sue090s/sue_cp090s pendientes de relevamiento). Hoy solo EnMemoria. */
interface RepositorioResultados
{
    public function guardar(Liquidacion $liquidacion): void;
}
