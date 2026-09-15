<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;

/** ANTIGUEDAD: básico devengado × % del modelo de antigüedad del convenio (ESCALA/LINEAL) según años completos. */
final class Antiguedad implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        if ($ctx->modeloAntiguedad === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: el convenio {$ctx->codigoConvenio} no tiene modelo de antigüedad asignado al {$ctx->fechaResolucion->format('Y-m-d')}.", 'motor_convenio_modelo_antiguedad');
        }
        $anios = $ctx->aniosAntiguedad();
        $porcentaje = $calc->resolutorAntiguedad->porcentaje($ctx->modeloAntiguedad, $anios);
        $base = $calc->basicoDevengado($ctx);

        return new ResultadoEstrategia($porcentaje->aplicarA($base), null, null, [
            'formula' => 'basico devengado × % antigüedad', 'anios' => $anios, 'modelo' => $ctx->modeloAntiguedad->codigo,
            'porcentaje' => $porcentaje->porciento(), 'base' => (string) $base,
        ]);
    }
}
