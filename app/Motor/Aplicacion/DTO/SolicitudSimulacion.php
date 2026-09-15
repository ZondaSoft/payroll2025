<?php

namespace App\Motor\Aplicacion\DTO;

use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;

/** Entrada del caso de uso SimularLiquidacion. El entorno viaja acá, nunca en el dominio. */
final readonly class SolicitudSimulacion
{
    public function __construct(
        public int $relacionLaboralId,
        public Periodo $periodo,
        public TipoLiquidacion $tipoLiquidacion,
        public Entorno $entorno,
        public int $diasLiquidados = 30,
        public string $factorJornada = '1',
        public string $condicionSindical = 'NO_INFORMADO',
    ) {}
}
