<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;

/**
 * El valor que un concepto toma en una categoría: lo que las fórmulas leen con
 * VALCAT(). Es la tolerancia horaria del 371/03 o el plus por pluralidad del
 * 40/89, que cambian de categoría en categoría.
 *
 * El modelo v2 lo separó de los parámetros a propósito: un parámetro con alcance
 * CATEGORIA y un valor por categoría son la misma cosa dicha de dos maneras, y
 * tener las dos obliga a decidir cuál gana. Acá el valor por categoría es VALCAT
 * y el alcance CATEGORIA desapareció de los parámetros.
 */
final readonly class ValorCategoriaConcepto
{
    public function __construct(
        public ?int $id,
        public int $idCategoria,
        public string $codigoConcepto,
        public BigDecimal $valor,
        public Vigencia $vigencia,
        public string $tipoValor = 'PORCENTAJE',
        public ?string $unidad = null,
        public EstadoValidacion $estadoValidacion = EstadoValidacion::SinConfirmar,
        public bool $activo = true,
    ) {}
}
