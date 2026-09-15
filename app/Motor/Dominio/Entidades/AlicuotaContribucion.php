<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class AlicuotaContribucion
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $encuadramiento,
        public int $idSubsistema,
        public string $codigoSubsistema,
        public Porcentaje $porcentaje,
        public string $baseAplicable,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
