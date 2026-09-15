<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioResultados;
use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;

/** Persistencia de resultados: fase futura. Hoy solo retiene en memoria (simulador). */
final class RepositorioResultadosEnMemoria implements RepositorioResultados
{
    /** @var list<Liquidacion> */
    public array $guardadas = [];

    public function guardar(Liquidacion $liquidacion): void
    {
        $this->guardadas[] = $liquidacion;
    }
}
