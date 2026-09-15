<?php

namespace App\Motor\Dominio\Enums;

enum EstadoValidacion: string
{
    case SinDato = 'SIN_DATO';
    case SinConfirmar = 'SIN_CONFIRMAR';
    case Confirmado = 'CONFIRMADO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SinDato => 'Sin dato',
            self::SinConfirmar => 'Sin confirmar',
            self::Confirmado => 'Confirmado',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
