<?php

namespace App\Motor\Dominio\Enums;

enum PoliticaEdicion: string
{
    case NoEditable = 'NO_EDITABLE';
    case Editable = 'EDITABLE';
    case EditableConJustificacion = 'EDITABLE_CON_JUSTIFICACION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::NoEditable => 'No editable',
            self::Editable => 'Editable',
            self::EditableConJustificacion => 'Editable con justificación',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
