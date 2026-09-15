<?php

namespace App\Motor\Dominio\Enums;

enum MetodoAntiguedad: string
{
    case Escala = 'ESCALA';
    case Lineal = 'LINEAL';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Escala => 'Escala por tramos',
            self::Lineal => 'Lineal (% por año)',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
