<?php

namespace App\Motor\Dominio\Enums;

enum EstadoIfNovedad: string
{
    case Normalizada = 'NORMALIZADA';
    case SinMapeo = 'SIN_MAPEO';
    case Ignorada = 'IGNORADA';
    case SinRelacionLaboral = 'SIN_RELACION_LABORAL';
    case Anulada = 'ANULADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Normalizada => 'Normalizada',
            self::SinMapeo => 'Sin mapeo',
            self::Ignorada => 'Ignorada',
            self::SinRelacionLaboral => 'Sin relación laboral',
            self::Anulada => 'Anulada',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
