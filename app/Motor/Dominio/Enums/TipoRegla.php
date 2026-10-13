<?php

namespace App\Motor\Dominio\Enums;

enum TipoRegla: string
{
    case Base = 'BASE';
    case Sustitutivo = 'SUSTITUTIVO';
    case Adicional = 'ADICIONAL';
    case Destino = 'DESTINO';
    /** Reemplaza un subsistema completo por otro destino con su propio porcentaje (RENATRE 1,5 % en lugar de FNE). */
    case SustitucionSubsistema = 'SUSTITUCION_SUBSISTEMA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Base => 'Base',
            self::Sustitutivo => 'Sustitutivo',
            self::Adicional => 'Adicional',
            self::Destino => 'Destino',
            self::SustitucionSubsistema => 'Sustitución de subsistema',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
