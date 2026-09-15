<?php

namespace App\Motor\Dominio\Enums;

enum Incidencia: string
{
    case Si = 'S';
    case No = 'N';
    case Pendiente = 'PENDIENTE';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Si => 'Sí',
            self::No => 'No',
            self::Pendiente => 'Pendiente',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
