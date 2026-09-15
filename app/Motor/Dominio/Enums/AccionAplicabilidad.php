<?php

namespace App\Motor\Dominio\Enums;

enum AccionAplicabilidad: string
{
    case Incluir = 'INCLUIR';
    case Excluir = 'EXCLUIR';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Incluir => 'Incluir',
            self::Excluir => 'Excluir',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
