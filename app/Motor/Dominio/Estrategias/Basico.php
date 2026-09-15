<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;

/** BASICO: básico de la categoría × días liquidados / DIVISOR_DIAS × factor de jornada (mockup basicoDevengado). */
final class Basico implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        $devengado = $calc->basicoDevengado($ctx);

        return new ResultadoEstrategia($devengado, null, null, [
            'formula' => 'basico × dias / DIVISOR_DIAS × factor', 'basico' => (string) $calc->basico($ctx), 'dias' => $ctx->diasLiquidados,
            'divisor' => (string) $calc->parametro('DIVISOR_DIAS', $ctx), 'factor' => (string) $ctx->factor(),
        ]);
    }
}
