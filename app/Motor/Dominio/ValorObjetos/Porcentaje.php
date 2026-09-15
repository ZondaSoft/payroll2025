<?php

namespace App\Motor\Dominio\ValorObjetos;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use JsonSerializable;
use Stringable;

/**
 * Porcentaje almacenado como fracción decimal (la spec guarda 10,77 % como 0.1077).
 */
final readonly class Porcentaje implements JsonSerializable, Stringable
{
    public const ESCALA = 6;

    private function __construct(private BigDecimal $valorFraccion) {}

    /** 0.11 → 11 % */
    public static function deFraccion(BigNumber|int|string $fraccion): self
    {
        return new self(BigDecimal::of(is_string($fraccion) ? trim($fraccion) : $fraccion)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    /** 11 → 11 % */
    public static function dePorciento(BigNumber|int|string $porciento): self
    {
        return new self(BigDecimal::of(is_string($porciento) ? trim($porciento) : $porciento)->dividedBy(100, self::ESCALA, RoundingMode::HALF_UP));
    }

    public static function cero(): self
    {
        return self::deFraccion(0);
    }

    public function fraccion(): BigDecimal
    {
        return $this->valorFraccion;
    }

    /** '11.0000' */
    public function porciento(int $decimales = 4): string
    {
        return (string) $this->valorFraccion->multipliedBy(100)->toScale($decimales, RoundingMode::HALF_UP);
    }

    public function aplicarA(Importe $importe): Importe
    {
        return $importe->por($this);
    }

    public function mas(self $otro): self
    {
        return new self($this->valorFraccion->plus($otro->valorFraccion)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    public function esCero(): bool
    {
        return $this->valorFraccion->isZero();
    }

    public function igualA(self $otro): bool
    {
        return $this->valorFraccion->compareTo($otro->valorFraccion) === 0;
    }

    public function __toString(): string
    {
        return (string) $this->valorFraccion;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
