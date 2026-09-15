<?php

namespace App\Motor\Dominio\ValorObjetos;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Período mensual de liquidación. Formato canónico del motor: YYYY-MM (CHAR(7),
 * como la spec). El resto del sistema usa YYYYMM (CHAR(6)): este VO traduce.
 */
final readonly class Periodo implements JsonSerializable, Stringable
{
    public const ZONA_HORARIA = 'America/Argentina/Buenos_Aires';

    private function __construct(public int $anio, public int $mes)
    {
        if ($anio < 1900 || $anio > 2100) {
            throw new InvalidArgumentException("Año de período fuera de rango: {$anio}.");
        }
        if ($mes < 1 || $mes > 12) {
            throw new InvalidArgumentException("Mes de período inválido: {$mes}.");
        }
    }

    /** Acepta 'YYYY-MM' o 'YYYYMM'. */
    public static function de(string $texto): self
    {
        $t = trim($texto);
        if (preg_match('/^(\d{4})-(\d{2})$/', $t, $m) || preg_match('/^(\d{4})(\d{2})$/', $t, $m)) {
            return new self((int) $m[1], (int) $m[2]);
        }
        throw new InvalidArgumentException("Período inválido: '{$texto}'. Se espera YYYY-MM o YYYYMM.");
    }

    public static function deAnioMes(int $anio, int $mes): self
    {
        return new self($anio, $mes);
    }

    public static function deFecha(DateTimeInterface $fecha): self
    {
        return new self((int) $fecha->format('Y'), (int) $fecha->format('m'));
    }

    public function primerDia(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->anio, $this->mes), new DateTimeZone(self::ZONA_HORARIA));
    }

    public function ultimoDia(): DateTimeImmutable
    {
        return $this->primerDia()->modify('last day of this month');
    }

    public function siguiente(): self
    {
        return $this->mes === 12 ? new self($this->anio + 1, 1) : new self($this->anio, $this->mes + 1);
    }

    public function anterior(): self
    {
        return $this->mes === 1 ? new self($this->anio - 1, 12) : new self($this->anio, $this->mes - 1);
    }

    /** Diferencia en meses (positiva si $this es posterior a $otro). */
    public function mesesDesde(self $otro): int
    {
        return ($this->anio - $otro->anio) * 12 + ($this->mes - $otro->mes);
    }

    public function comparar(self $otro): int
    {
        return [$this->anio, $this->mes] <=> [$otro->anio, $otro->mes];
    }

    public function igualA(self $otro): bool
    {
        return $this->comparar($otro) === 0;
    }

    public function contiene(DateTimeInterface $fecha): bool
    {
        return $fecha->format('Y-m') === $this->__toString();
    }

    /** YYYY-MM (formato del motor). */
    public function __toString(): string
    {
        return sprintf('%04d-%02d', $this->anio, $this->mes);
    }

    /** YYYYMM (formato del resto del sistema: sue090s, sue100s, lsd_topes). */
    public function yyyymm(): string
    {
        return sprintf('%04d%02d', $this->anio, $this->mes);
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
