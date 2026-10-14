<?php

namespace App\Motor\Dominio\Formulas;

use RuntimeException;

/** Error de lenguaje: lleva el código del contrato y la posición en la expresión. */
final class ErrorDsl extends RuntimeException
{
    public function __construct(public readonly string $codigo, string $mensaje, public readonly int $posicion = 0)
    {
        parent::__construct($mensaje);
    }

    public static function en(string $codigo, string $mensaje, int $posicion = 0): self
    {
        return new self($codigo, $mensaje, $posicion);
    }
}
