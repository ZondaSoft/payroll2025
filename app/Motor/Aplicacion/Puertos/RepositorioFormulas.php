<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Formulas\VersionFormula;
use DateTimeImmutable;

/**
 * Las versiones ejecutables vigentes: la parte expr_* de motor_concepto_versiones
 * con sus novedades declaradas y sus literales justificados.
 *
 * Es distinto de RepositorioVersiones, que entrega el snapshot completo de la
 * versión para auditoría; acá sólo viaja lo que el DSL necesita para evaluar.
 */
interface RepositorioFormulas
{
    /** @return list<VersionFormula> todas las variantes vigentes a la fecha */
    public function vigentesA(DateTimeImmutable $fecha): array;
}
