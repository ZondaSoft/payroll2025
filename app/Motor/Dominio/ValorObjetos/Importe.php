<?php

namespace App\Motor\Dominio\ValorObjetos;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Importe monetario con aritmética decimal exacta (RT-011: nunca float).
 * Escala interna 6; el redondeo de negocio se aplica explícitamente con redondeado().
 */
final readonly class Importe implements JsonSerializable, Stringable
{
    public const ESCALA = 6;

    private function __construct(public BigDecimal $valor) {}

    public static function de(BigNumber|int|string $valor): self
    {
        if (is_string($valor)) {
            $valor = trim($valor);
            if ($valor === '') {
                throw new InvalidArgumentException('Importe vacío.');
            }
        }

        return new self(BigDecimal::of($valor)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    public static function cero(): self
    {
        return self::de(0);
    }

    public function mas(self $otro): self
    {
        return new self($this->valor->plus($otro->valor)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    public function menos(self $otro): self
    {
        return new self($this->valor->minus($otro->valor)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    public function por(BigNumber|int|string|Porcentaje $factor): self
    {
        $f = $factor instanceof Porcentaje ? $factor->fraccion() : BigDecimal::of($factor);

        return new self($this->valor->multipliedBy($f)->toScale(self::ESCALA, RoundingMode::HALF_UP));
    }

    public function dividido(BigNumber|int|string $divisor): self
    {
        $d = BigDecimal::of($divisor);
        if ($d->isZero()) {
            throw new InvalidArgumentException('División por cero.');
        }

        return new self($this->valor->dividedBy($d, self::ESCALA, RoundingMode::HALF_UP));
    }

    public function negado(): self
    {
        return new self($this->valor->negated());
    }

    public function redondeado(int $decimales): self
    {
        if ($decimales < 0 || $decimales > self::ESCALA) {
            throw new InvalidArgumentException("Decimales de redondeo fuera de rango: {$decimales}.");
        }

        return new self($this->valor->toScale($decimales, RoundingMode::HALF_UP)->toScale(self::ESCALA));
    }

    public function esCero(): bool
    {
        return $this->valor->isZero();
    }

    public function esNegativo(): bool
    {
        return $this->valor->isNegative();
    }

    public function esPositivo(): bool
    {
        return $this->valor->isPositive();
    }

    public function comparar(self $otro): int
    {
        return $this->valor->compareTo($otro->valor);
    }

    public function igualA(self $otro): bool
    {
        return $this->comparar($otro) === 0;
    }

    public function mayorQue(self $otro): bool
    {
        return $this->comparar($otro) > 0;
    }

    public function menorQue(self $otro): bool
    {
        return $this->comparar($otro) < 0;
    }

    public static function min(self $a, self $b): self
    {
        return $a->menorQue($b) ? $a : $b;
    }

    public static function max(self $a, self $b): self
    {
        return $a->mayorQue($b) ? $a : $b;
    }

    /** Representación con N decimales (para recibo / UI). */
    public function aTexto(int $decimales = 2): string
    {
        return (string) $this->valor->toScale($decimales, RoundingMode::HALF_UP);
    }

    /** Escala 6, para persistir y para la traza. */
    public function __toString(): string
    {
        return (string) $this->valor;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
