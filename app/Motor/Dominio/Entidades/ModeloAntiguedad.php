<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\MetodoAntiguedad;
use App\Motor\Dominio\ValorObjetos\Porcentaje;

final readonly class ModeloAntiguedad
{
    /** @param list<TramoAntiguedad> $tramos */
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public MetodoAntiguedad $metodo,
        public ?Porcentaje $porcentajePorAnio,
        public ?Porcentaje $porcentajeMaximo,
        public array $tramos,
        public EstadoValidacion $estadoValidacion,
        public bool $activo = true,
    ) {}
}
