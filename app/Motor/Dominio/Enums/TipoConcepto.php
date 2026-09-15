<?php

namespace App\Motor\Dominio\Enums;

enum TipoConcepto: string
{
    case Haber = 'HABER';
    case Descuento = 'DESCUENTO';
    case Contribucion = 'CONTRIBUCION';
    case Informativo = 'INFORMATIVO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Haber => 'Haber',
            self::Descuento => 'Descuento',
            self::Contribucion => 'Contribución',
            self::Informativo => 'Informativo',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
