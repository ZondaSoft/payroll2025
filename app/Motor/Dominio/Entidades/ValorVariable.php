<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;

/** El valor de una variable en un ámbito y una vigencia (motor_valores_variable). */
final readonly class ValorVariable
{
    public function __construct(
        public ?int $id,
        public string $codigo,
        public AlcanceParametro $alcance,
        public ?int $idConvenio,
        public ?int $idCategoria,
        public ?int $relacionLaboralId,
        public BigDecimal $valor,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
        public ?string $fuente = null,
    ) {}

    /** Misma precedencia que los parámetros: gana el ámbito más específico. */
    public function nivel(): int
    {
        return match ($this->alcance) {
            AlcanceParametro::Trabajador => 5,
            AlcanceParametro::Empresa => 4,
            AlcanceParametro::Categoria => 3,
            AlcanceParametro::Convenio => 2,
            AlcanceParametro::General => 1,
        };
    }
}
