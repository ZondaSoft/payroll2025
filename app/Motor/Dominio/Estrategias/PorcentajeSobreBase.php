<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;
use App\Motor\Dominio\ValorObjetos\Porcentaje;

/**
 * PORCENTAJE_SOBRE_BASE: parámetro (fracción) × base. La base es la base de Zonda
 * indicada en codigo_base_zonda o, si es NULL, el básico devengado (ADTIT, ASIST,
 * MANFON, PLUSPLUR, AD562, ACDSIND562 → sobre básico; ADICNR20 → sobre BASE_NO_REMUNERATIVA).
 */
final class PorcentajeSobreBase implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        $codigoParametro = $c->codigoParametro ?? throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: PORCENTAJE_SOBRE_BASE sin codigo_parametro.", 'motor_conceptos', 'codigo_parametro');
        $porcentaje = Porcentaje::deFraccion($calc->parametro($codigoParametro, $ctx));
        if ($c->codigoBaseZonda !== null) {
            $base = $calc->baseZonda($c->codigoBaseZonda, $ctx);
            $origen = "base {$c->codigoBaseZonda}";
        } else {
            $base = $calc->basicoDevengado($ctx);
            $origen = 'básico devengado';
        }

        return new ResultadoEstrategia($porcentaje->aplicarA($base), null, null, [
            'formula' => "{$porcentaje->porciento()} % × {$origen}", 'base' => (string) $base, 'parametro' => $codigoParametro,
        ]);
    }
}
