<?php

namespace App\Motor\Dominio\Enums;

enum TipoRegla: string
{
    case Base = 'BASE';
    case Sustitutivo = 'SUSTITUTIVO';
    case Adicional = 'ADICIONAL';
    case Destino = 'DESTINO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Base => 'Base',
            self::Sustitutivo => 'Sustitutivo',
            self::Adicional => 'Adicional',
            self::Destino => 'Destino',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
