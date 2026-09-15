<?php

namespace App\Motor\Dominio\Enums;

enum SujetoObligado: string
{
    case Trabajador = 'TRABAJADOR';
    case Empleador = 'EMPLEADOR';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Trabajador => 'Trabajador',
            self::Empleador => 'Empleador',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
