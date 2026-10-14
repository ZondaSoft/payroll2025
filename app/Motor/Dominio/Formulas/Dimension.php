<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Álgebra dimensional del DSL (§18). Una unidad es un mapa de exponentes:
 * ARS = {ARS:1}, ARS/HORA = {ARS:1, HORAS:-1}.
 *
 * Los escalares (NUMERIC, PORCENTAJE, COEFICIENTE) son dimensionalmente vacíos:
 * distintos por significado, iguales por dimensión, como fija el contrato.
 *
 * INDETERMINADA es la unidad que el catálogo no declara. Es compatible con todo
 * y nunca produce error dimensional: se informa como advertencia "unidad sin
 * declarar", que al publicar se convierte en CFG_UNIDAD_SIN_DECLARAR. Nunca se
 * inventa una unidad.
 */
final readonly class Dimension
{
    private const BOOLEANA = 'BOOLEAN';

    /** @param array<string, int> $exponentes */
    private function __construct(
        public array $exponentes = [],
        public bool $indeterminada = false,
        public bool $booleana = false,
    ) {}

    public static function escalar(): self
    {
        return new self;
    }

    public static function booleano(): self
    {
        return new self(booleana: true);
    }

    public static function indeterminada(): self
    {
        return new self(indeterminada: true);
    }

    public static function base(string $unidad, int $exponente = 1): self
    {
        return new self([$unidad => $exponente]);
    }

    /** Unidad declarada en el catálogo → dimensión. Lo que no reconoce queda indeterminado. */
    public static function deUnidad(?string $unidad): self
    {
        if ($unidad === null || $unidad === '') {
            return self::indeterminada();
        }

        return match ($unidad) {
            'ARS' => self::base('ARS'),
            'HORAS' => self::base('HORAS'),
            'DIAS' => self::base('DIAS'),
            'KM' => self::base('KM'),
            'UNIDADES', 'CANTIDAD' => self::base('UNIDADES'),
            'NUMERIC', 'PORCENTAJE', 'COEFICIENTE' => self::escalar(),
            'ARS/KM' => self::base('ARS')->dividido(self::base('KM')),
            'ARS/HORA' => self::base('ARS')->dividido(self::base('HORAS')),
            'ARS/DIA' => self::base('ARS')->dividido(self::base('DIAS')),
            'ARS/UNIDAD' => self::base('ARS')->dividido(self::base('UNIDADES')),
            default => self::indeterminada(),
        };
    }

    public function por(self $otra): self
    {
        if ($this->indeterminada || $otra->indeterminada) {
            return self::indeterminada();
        }
        $r = $this->exponentes;
        foreach ($otra->exponentes as $k => $e) {
            $r[$k] = ($r[$k] ?? 0) + $e;
            if ($r[$k] === 0) {
                unset($r[$k]);
            }
        }

        return new self($r);
    }

    public function dividido(self $otra): self
    {
        if ($this->indeterminada || $otra->indeterminada) {
            return self::indeterminada();
        }
        $inverso = [];
        foreach ($otra->exponentes as $k => $e) {
            $inverso[$k] = -$e;
        }

        return $this->por(new self($inverso));
    }

    /** Con una unidad sin declarar no se puede afirmar incompatibilidad: no es error. */
    public function igualA(self $otra): bool
    {
        if ($this->indeterminada || $otra->indeterminada) {
            return true;
        }
        if ($this->booleana || $otra->booleana) {
            return $this->booleana === $otra->booleana;
        }
        if (count($this->exponentes) !== count($otra->exponentes)) {
            return false;
        }
        foreach ($this->exponentes as $k => $e) {
            if (($otra->exponentes[$k] ?? null) !== $e) {
                return false;
            }
        }

        return true;
    }

    public function esEscalar(): bool
    {
        return ! $this->indeterminada && ! $this->booleana && $this->exponentes === [];
    }

    /** Una magnitud es una dimensión con unidad propia: ARS, HORAS, KM… */
    public function esMagnitud(): bool
    {
        return ! $this->indeterminada && ! $this->booleana && $this->exponentes !== [];
    }

    public function __toString(): string
    {
        if ($this->booleana) {
            return self::BOOLEANA;
        }
        if ($this->indeterminada) {
            return 'SIN DECLARAR';
        }
        $numerador = [];
        $denominador = [];
        foreach ($this->exponentes as $k => $e) {
            $etiqueta = abs($e) === 1 ? $k : $k.'^'.abs($e);
            if ($e > 0) {
                $numerador[] = $etiqueta;
            } else {
                $denominador[] = $etiqueta;
            }
        }
        if ($numerador === [] && $denominador === []) {
            return 'ESCALAR';
        }

        return (implode('·', $numerador) ?: '1').($denominador !== [] ? '/'.implode('·', $denominador) : '');
    }
}
