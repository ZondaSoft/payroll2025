<?php

namespace App\Motor\Dominio\Enums;

enum ExigenciaConcepto: string
{
    case Obligatorio = 'OBLIGATORIO';
    case Condicional = 'CONDICIONAL';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Obligatorio => 'Obligatorio',
            self::Condicional => 'Condicional',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
