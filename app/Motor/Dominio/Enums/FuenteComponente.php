<?php

namespace App\Motor\Dominio\Enums;

enum FuenteComponente: string
{
    case Formula = 'FORMULA';
    case Novedad = 'NOVEDAD';
    case Parametro = 'PARAMETRO';
    case BaseZonda = 'BASE_ZONDA';
    case Basico = 'BASICO';
    case Manual = 'MANUAL';
    case ModuloSistema = 'MODULO_SISTEMA';
    case NoAplica = 'NO_APLICA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Formula => 'Fórmula',
            self::Novedad => 'Novedad',
            self::Parametro => 'Parámetro',
            self::BaseZonda => 'Base de Zonda',
            self::Basico => 'Sueldo básico',
            self::Manual => 'Manual',
            self::ModuloSistema => 'Módulo del sistema',
            self::NoAplica => 'No aplica',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
