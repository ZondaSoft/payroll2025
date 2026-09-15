<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;

/** MODULO_SISTEMA: no se ejecuta como haber; lo resuelve el bloque contributivo (aportes/contribuciones). */
final class ModuloSistema implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        throw MotorBloqueado::porque(CodigoBloqueo::EstrategiaSinEjecutor, "{$c->codigo} es MODULO_SISTEMA: lo calcula el módulo de seguridad social, no una estrategia de haber.", 'motor_conceptos', 'estrategia_calculo');
    }
}
