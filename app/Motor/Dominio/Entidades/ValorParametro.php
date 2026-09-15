<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;

final readonly class ValorParametro
{
    public function __construct(
        public ?int $id,
        public int $idParametro,
        public string $codigoParametro,
        public AlcanceParametro $alcance,
        public ?int $idConvenio,
        public ?int $idCategoria,
        public ?int $idEmpresa,
        public ?int $relacionLaboralId,
        public BigDecimal $valor,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}

    /** Nivel de precedencia: TRABAJADOR 5 > EMPRESA 4 > CATEGORIA 3 > CONVENIO 2 > GENERAL 1. */
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
