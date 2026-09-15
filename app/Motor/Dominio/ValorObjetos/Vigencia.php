<?php

namespace App\Motor\Dominio\ValorObjetos;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Rango de vigencia por fecha (sin hora). vigencia_hasta NULL = abierta (RT-007).
 */
final readonly class Vigencia implements JsonSerializable
{
    public DateTimeImmutable $desde;

    public ?DateTimeImmutable $hasta;

    public function __construct(DateTimeInterface $desde, ?DateTimeInterface $hasta = null)
    {
        $this->desde = self::soloFecha($desde);
        $this->hasta = $hasta === null ? null : self::soloFecha($hasta);

        if ($this->hasta !== null && $this->hasta < $this->desde) {
            throw new InvalidArgumentException('Vigencia hasta no puede ser anterior a vigencia desde.');
        }
    }

    /** Acepta 'YYYY-MM-DD' (o null en hasta). */
    public static function de(string $desde, ?string $hasta = null): self
    {
        return new self(self::parsear($desde), $hasta === null || $hasta === '' ? null : self::parsear($hasta));
    }

    public static function abiertaDesde(DateTimeInterface $desde): self
    {
        return new self($desde, null);
    }

    public function esAbierta(): bool
    {
        return $this->hasta === null;
    }

    public function contiene(DateTimeInterface $fecha): bool
    {
        $f = self::soloFecha($fecha);

        return $f >= $this->desde && ($this->hasta === null || $f <= $this->hasta);
    }

    public function seSuperponeCon(self $otra): bool
    {
        $finPropio = $this->hasta ?? new DateTimeImmutable('9999-12-31', new DateTimeZone(Periodo::ZONA_HORARIA));
        $finOtra = $otra->hasta ?? new DateTimeImmutable('9999-12-31', new DateTimeZone(Periodo::ZONA_HORARIA));

        return $this->desde <= $finOtra && $otra->desde <= $finPropio;
    }

    /** Nueva vigencia cerrada el día anterior a $fecha (para cerrar la versión vigente al publicar otra). */
    public function cerradaAntesDe(DateTimeInterface $fecha): self
    {
        $diaAnterior = self::soloFecha($fecha)->modify('-1 day');

        return new self($this->desde, $diaAnterior);
    }

    public function __toString(): string
    {
        return $this->desde->format('Y-m-d').' → '.($this->hasta?->format('Y-m-d') ?? '∞');
    }

    /** @return array{desde: string, hasta: ?string} */
    public function jsonSerialize(): array
    {
        return ['desde' => $this->desde->format('Y-m-d'), 'hasta' => $this->hasta?->format('Y-m-d')];
    }

    public static function parsear(string $fecha): DateTimeImmutable
    {
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha), new DateTimeZone(Periodo::ZONA_HORARIA));
        if ($f === false || $f->format('Y-m-d') !== trim($fecha)) {
            throw new InvalidArgumentException("Fecha inválida: '{$fecha}' (se espera aaaa-mm-dd).");
        }

        return $f;
    }

    private static function soloFecha(DateTimeInterface $fecha): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('!Y-m-d', $fecha->format('Y-m-d'), new DateTimeZone(Periodo::ZONA_HORARIA));
    }
}
