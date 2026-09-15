<?php

namespace App\Motor\Dominio\Enums;

enum NivelValidacion: string
{
    case Error = 'ERROR';
    case Aviso = 'AVISO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Error => 'Error',
            self::Aviso => 'Aviso',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
