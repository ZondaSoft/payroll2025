<?php

namespace App\Motor\Dominio\Enums;

enum Entorno: string
{
    case Prueba = 'PRUEBA';
    case Produccion = 'PRODUCCION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Prueba => 'Prueba',
            self::Produccion => 'Producción',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
