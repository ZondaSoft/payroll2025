<?php

namespace App\Motor\Dominio\Enums;

enum Signo: string
{
    case Credito = 'CREDITO';
    case Debito = 'DEBITO';
    case Neutro = 'NEUTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Credito => 'Crédito',
            self::Debito => 'Débito',
            self::Neutro => 'Neutro',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
