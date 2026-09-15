<?php

namespace App\Motor\Dominio\Enums;

enum EstadoRelacion: string
{
    case Vigente = 'VIGENTE';
    case Baja = 'BAJA';
    case InconsistenteOrigen = 'INCONSISTENTE_ORIGEN';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Baja => 'Baja',
            self::InconsistenteOrigen => 'Inconsistente con el origen',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
