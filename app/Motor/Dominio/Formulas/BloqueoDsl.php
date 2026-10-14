<?php

namespace App\Motor\Dominio\Formulas;

use RuntimeException;

/**
 * El evaluador no pudo producir un número. Lleva el código del contrato
 * (VAR_SIN_VALOR, PARAM_SIN_VALOR, DIVISION_CERO, CONC_BLOQUEADO…) para que la
 * capa de aplicabilidad decida si el concepto queda BLOQUEADO o NO_APLICA.
 *
 * Nunca se traduce a cero: un dato que falta no es un importe en cero.
 */
final class BloqueoDsl extends RuntimeException
{
    public function __construct(public readonly string $codigo, string $contexto)
    {
        parent::__construct($contexto);
    }

    public static function porque(string $codigo, string $contexto): self
    {
        return new self($codigo, $contexto);
    }
}
