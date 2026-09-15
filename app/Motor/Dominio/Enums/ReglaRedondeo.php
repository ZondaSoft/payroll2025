<?php

namespace App\Motor\Dominio\Enums;

enum ReglaRedondeo: string
{
    case SinRedondeo = 'SIN_REDONDEO';
    case Decimal = 'DECIMAL';
    case Entero = 'ENTERO';
    case Parametrizada = 'PARAMETRIZADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SinRedondeo => 'Sin redondeo',
            self::Decimal => 'Dos decimales',
            self::Entero => 'Entero',
            self::Parametrizada => 'Parametrizada',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
