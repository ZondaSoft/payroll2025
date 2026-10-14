<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ValorCategoriaConcepto;
use DateTimeImmutable;

interface RepositorioValoresCategoria
{
    /** @return list<ValorCategoriaConcepto> vigentes a la fecha, de todas las categorías */
    public function vigentes(DateTimeImmutable $fecha): array;
}
