<?php

namespace App\Motor\Dominio\Enums;

enum TipoRepeticion: string
{
    case TodosLosMeses = 'TODOS_LOS_MESES';
    case CadaNMeses = 'CADA_N_MESES';
    case DuranteNMeses = 'DURANTE_N_MESES';
    case MesesEspecificos = 'MESES_ESPECIFICOS';
    case SinRestriccion = 'SIN_RESTRICCION_PERIODICA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::TodosLosMeses => 'Todos los meses',
            self::CadaNMeses => 'Cada N meses',
            self::DuranteNMeses => 'Durante N meses',
            self::MesesEspecificos => 'Meses específicos',
            self::SinRestriccion => 'Sin restricción periódica',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
