<?php

namespace App\Motor\Dominio\Enums;

enum TipoAmbito: string
{
    case General = 'GENERAL';
    case Convenio = 'CONVENIO';
    case Categoria = 'CATEGORIA';
    case Empresa = 'EMPRESA';
    case Trabajador = 'TRABAJADOR';

    public function etiqueta(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Convenio => 'Convenio',
            self::Categoria => 'Categoría',
            self::Empresa => 'Empresa',
            self::Trabajador => 'Trabajador',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
