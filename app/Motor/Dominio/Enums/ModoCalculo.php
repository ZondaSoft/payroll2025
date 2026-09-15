<?php

namespace App\Motor\Dominio\Enums;

enum ModoCalculo: string
{
    case Formula = 'FORMULA';
    case Novedad = 'NOVEDAD';
    case ImporteFijo = 'IMPORTE_FIJO';
    case Parametro = 'PARAMETRO';
    case Tabla = 'TABLA';
    case ModuloSistema = 'MODULO_SISTEMA';
    case Manual = 'MANUAL';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Formula => 'Fórmula',
            self::Novedad => 'Novedad',
            self::ImporteFijo => 'Importe fijo',
            self::Parametro => 'Parámetro',
            self::Tabla => 'Tabla',
            self::ModuloSistema => 'Módulo del sistema',
            self::Manual => 'Manual',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
