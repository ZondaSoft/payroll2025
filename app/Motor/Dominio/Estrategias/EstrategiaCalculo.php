<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;
use App\Motor\Dominio\ValorObjetos\Importe;

/**
 * Una estrategia calcula el importe exacto (sin redondear) de un concepto de
 * haber. Sin DSL: cada estrategia es código tipado y testeable. Toda falta de
 * dato lanza MotorBloqueado (fail-safe: nunca se transforma en cero).
 */
interface EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia;
}
