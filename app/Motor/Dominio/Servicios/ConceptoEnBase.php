<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\ValorObjetos\Importe;

/**
 * Lo que la composición de una base ARCA necesita saber de un concepto liquidado:
 * si se calculó (y cuánto) o si quedó bloqueado (y por qué).
 *
 * Los dos importan. Un concepto bloqueado no tiene importe, pero si integra la
 * base (incidencia S) la base tampoco se puede afirmar (PE-2).
 */
final readonly class ConceptoEnBase
{
    private function __construct(
        public string $codigo,
        public bool $calculado,
        public ?Importe $importe,
        public ?string $codigoError,
    ) {}

    public static function calculado(string $codigo, Importe $importe): self
    {
        return new self($codigo, true, $importe, null);
    }

    public static function bloqueado(string $codigo, ?string $codigoError): self
    {
        return new self($codigo, false, null, $codigoError);
    }
}
