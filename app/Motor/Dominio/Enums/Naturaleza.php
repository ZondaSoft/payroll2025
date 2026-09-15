<?php

namespace App\Motor\Dominio\Enums;

enum Naturaleza: string
{
    case Remunerativo = 'REMUNERATIVO';
    case NoRemunerativo = 'NO_REMUNERATIVO';
    case Descuento = 'DESCUENTO';
    case Contribucion = 'CONTRIBUCION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Remunerativo => 'Remunerativo',
            self::NoRemunerativo => 'No remunerativo',
            self::Descuento => 'Descuento',
            self::Contribucion => 'Contribución',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
