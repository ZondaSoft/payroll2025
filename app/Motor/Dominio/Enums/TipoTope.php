<?php

namespace App\Motor\Dominio\Enums;

enum TipoTope: string
{
    case Maximo = 'MAXIMO';
    case Minimo = 'MINIMO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Maximo => 'Máximo',
            self::Minimo => 'Mínimo',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
