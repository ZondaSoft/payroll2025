<?php

namespace App\Motor\Dominio\Enums;

enum TipoCambio: string
{
    case Alta = 'ALTA';
    case Paritaria = 'PARITARIA';
    case Normativo = 'NORMATIVO';
    case Correccion = 'CORRECCION';
    case CambioFuncional = 'CAMBIO_FUNCIONAL';
    case Otro = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Paritaria => 'Paritaria',
            self::Normativo => 'Normativo',
            self::Correccion => 'Corrección',
            self::CambioFuncional => 'Cambio funcional',
            self::Otro => 'Otro',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
