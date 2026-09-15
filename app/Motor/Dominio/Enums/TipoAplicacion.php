<?php

namespace App\Motor\Dominio\Enums;

enum TipoAplicacion: string
{
    case Aporte = 'APORTE';
    case Contribucion = 'CONTRIBUCION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aporte => 'Aporte',
            self::Contribucion => 'Contribución',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
