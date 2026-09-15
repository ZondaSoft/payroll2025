<?php

namespace App\Motor\Dominio\Enums;

enum TipoLiquidacion: string
{
    case Normal = 'NORMAL';
    case Sac = 'SAC';
    case Final = 'FINAL';
    case Vacaciones = 'VACACIONES';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Sac => 'SAC',
            self::Final => 'Liquidación final',
            self::Vacaciones => 'Vacaciones',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
