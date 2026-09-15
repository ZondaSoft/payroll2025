<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;
use App\Motor\Dominio\ValorObjetos\Importe;

/** IMPORTE_DIRECTO: parámetro | importe_fijo | valor de una novedad monetaria | MANUAL (no se ejecuta, se avisa). */
final class ImporteDirecto implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        switch ($c->fuenteImporte) {
            case FuenteComponente::Parametro:
                $codigo = $c->codigoParametro ?? throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: IMPORTE_DIRECTO por parámetro sin codigo_parametro.", 'motor_conceptos', 'codigo_parametro');

                return new ResultadoEstrategia(Importe::de($calc->parametro($codigo, $ctx)), null, null, ['formula' => "parámetro {$codigo}"]);
            case FuenteComponente::Novedad:
                $codigo = $c->codigoNovedadImporte ?? throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: IMPORTE_DIRECTO por novedad sin codigo_novedad_importe.", 'motor_conceptos', 'codigo_novedad_importe');
                $valor = $ctx->valorNovedad($codigo);
                if ($valor === null) {
                    if ($c->requiereNovedad) {
                        throw MotorBloqueado::porque(CodigoBloqueo::NovedadObligatoriaAusente, "Novedad monetaria '{$codigo}' ausente para {$c->codigo}: el motor bloquea.", "motor_novedades:{$codigo}");
                    }
                    throw new SinNovedad($codigo);
                }

                return new ResultadoEstrategia(Importe::de($valor), null, null, ['formula' => "valor de la novedad {$codigo}"]);
            case FuenteComponente::Manual:
                return ResultadoEstrategia::manual();
            default:
                if ($c->importeFijo !== null) {
                    return new ResultadoEstrategia($c->importeFijo, null, null, ['formula' => 'importe fijo']);
                }
                throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: IMPORTE_DIRECTO sin fuente de importe ni importe fijo.", 'motor_conceptos', 'fuente_importe');
        }
    }
}
