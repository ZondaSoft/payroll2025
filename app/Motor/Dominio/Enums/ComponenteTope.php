<?php

namespace App\Motor\Dominio\Enums;

enum ComponenteTope: string
{
    case Mensual = 'MENSUAL';
    case SacCompleto = 'SAC_COMPLETO';
    case SacProporcional = 'SAC_PROPORCIONAL';
    case Vacaciones = 'VACACIONES';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Mensual => 'Remuneración mensual',
            self::SacCompleto => 'SAC completo',
            self::SacProporcional => 'SAC proporcional',
            self::Vacaciones => 'Vacaciones',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
