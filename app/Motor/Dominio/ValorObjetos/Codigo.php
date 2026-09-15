<?php

namespace App\Motor\Dominio\ValorObjetos;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Código funcional estable (R8 del mockup): letras, números y guión bajo.
 * Se normaliza a mayúsculas sin espacios para que dominio y BD (collation _ci)
 * coincidan siempre.
 */
readonly class Codigo implements JsonSerializable, Stringable
{
    public const LARGO_MAXIMO = 50;

    final protected function __construct(public string $valor) {}

    public static function de(string $valor): static
    {
        $normalizado = strtoupper(trim($valor));

        if ($normalizado === '') {
            throw new InvalidArgumentException('El código no puede estar vacío.');
        }
        if (strlen($normalizado) > self::LARGO_MAXIMO) {
            throw new InvalidArgumentException("El código '{$normalizado}' supera los ".self::LARGO_MAXIMO.' caracteres.');
        }
        if (! preg_match('/^[A-Z0-9_]+$/', $normalizado)) {
            throw new InvalidArgumentException("El código '{$valor}' solo admite letras, números y guión bajo (sin espacios ni otros signos).");
        }

        return new static($normalizado);
    }

    public function igualA(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }

    public function jsonSerialize(): string
    {
        return $this->valor;
    }
}
