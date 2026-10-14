<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Entidades\Variable;
use DateTimeImmutable;

interface RepositorioVariables
{
    /** @return list<Variable> */
    public function catalogo(): array;

    /** @return list<ValorVariable> todos los valores vigentes a la fecha, de todos los ámbitos */
    public function valoresVigentes(DateTimeImmutable $fecha): array;
}
