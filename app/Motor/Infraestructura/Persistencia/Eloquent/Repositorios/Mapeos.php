<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/** Conversiones fila ↔ VO compartidas por los repositorios Eloquent. */
final class Mapeos
{
    public static function vigencia(object $f): Vigencia
    {
        return Vigencia::de((string) $f->vigencia_desde, $f->vigencia_hasta === null ? null : (string) $f->vigencia_hasta);
    }

    public static function estado(object $f): EstadoValidacion
    {
        return EstadoValidacion::from((string) ($f->estado_validacion ?? 'SIN_CONFIRMAR'));
    }

    public static function porcentaje(mixed $v): ?Porcentaje
    {
        return $v === null || $v === '' ? null : Porcentaje::deFraccion((string) $v);
    }

    public static function importe(mixed $v): ?Importe
    {
        return $v === null || $v === '' ? null : Importe::de((string) $v);
    }

    public static function decimal(mixed $v): ?BigDecimal
    {
        return $v === null || $v === '' ? null : BigDecimal::of((string) $v);
    }

    public static function fecha(DateTimeInterface $f): string
    {
        return $f->format('Y-m-d');
    }

    /** Condición SQL de vigencia a una fecha (vigencia_hasta NULL = abierta). */
    public static function vigenteA(Builder $q, DateTimeInterface $fecha, string $prefijo = ''): Builder
    {
        $d = self::fecha($fecha);

        return $q->where($prefijo.'vigencia_desde', '<=', $d)
            ->where(fn ($w) => $w->whereNull($prefijo.'vigencia_hasta')->orWhere($prefijo.'vigencia_hasta', '>=', $d));
    }

    /** @return array<string, mixed> columnas de vigencia para escribir */
    public static function columnasVigencia(Vigencia $v): array
    {
        return ['vigencia_desde' => $v->desde->format('Y-m-d'), 'vigencia_hasta' => $v->hasta?->format('Y-m-d')];
    }
}
