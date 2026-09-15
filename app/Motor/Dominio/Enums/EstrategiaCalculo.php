<?php

namespace App\Motor\Dominio\Enums;

enum EstrategiaCalculo: string
{
    case ImporteDirecto = 'IMPORTE_DIRECTO';
    case CantidadPorValor = 'CANTIDAD_X_VALOR';
    case Basico = 'BASICO';
    case Antiguedad = 'ANTIGUEDAD';
    case PorcentajeSobreBase = 'PORCENTAJE_SOBRE_BASE';
    case ModuloSistema = 'MODULO_SISTEMA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ImporteDirecto => 'Importe directo',
            self::CantidadPorValor => 'Cantidad × valor',
            self::Basico => 'Sueldo básico',
            self::Antiguedad => 'Antigüedad',
            self::PorcentajeSobreBase => 'Porcentaje sobre base',
            self::ModuloSistema => 'Módulo del sistema',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
