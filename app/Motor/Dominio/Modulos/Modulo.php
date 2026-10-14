<?php

namespace App\Motor\Dominio\Modulos;

use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use Brick\Math\BigDecimal;

/**
 * Un módulo del motor: la mecánica que no se puede escribir como fórmula.
 *
 * Es el punto de extensión del §16 del contrato: lo nuevo entra como módulo y no
 * como sintaxis del DSL, que queda cerrado. Un módulo declara sus dependencias
 * (para que el grafo las ordene) y devuelve un único valor tipado; si le falta un
 * dato, lanza — nunca devuelve cero.
 */
interface Modulo
{
    /** Código con el que las fórmulas lo invocan: MOD(ANTIGUEDAD.PORCENTAJE). */
    public function codigo(): string;

    /** Unidad del resultado, para el álgebra dimensional: PORCENTAJE | ARS | DIAS | HORAS | NUMERIC. */
    public function unidad(): string;

    /** @return list<string> dependencias declaradas: 'VAR(ANTANIO)', 'CONC(BASICO)', 'BASE(…)' */
    public function dependencias(): array;

    public function resolver(ContextoRelacionLaboral $ctx): BigDecimal;
}
