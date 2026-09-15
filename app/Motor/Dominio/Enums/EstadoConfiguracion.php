<?php

namespace App\Motor\Dominio\Enums;

enum EstadoConfiguracion: string
{
    case Borrador = 'BORRADOR';
    case Publicado = 'PUBLICADO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Publicado => 'Publicado',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
