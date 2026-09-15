<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\EntornoCalculo;
use App\Motor\Dominio\ValorObjetos\Importe;
use Brick\Math\BigDecimal;

/**
 * CANTIDAD_X_VALOR: cantidad (novedad | parámetro) × valor unitario (parámetro |
 * hora normal de una base Zonda | básico diario) × factor_valor.
 * Cubre HE50/HE100/FERTRABHS (hora normal × recargo), KMREC/CTRLCD/PERMFR* (tarifa),
 * TOLHOR (parámetro de horas × hora normal), FERTRAB (días × básico diario).
 */
final class CantidadPorValor implements EstrategiaCalculo
{
    public function calcular(Concepto $c, ContextoRelacionLaboral $ctx, EntornoCalculo $calc): ResultadoEstrategia
    {
        $detalle = ['formula' => 'cantidad × valor unitario × factor'];

        // --- cantidad ---
        $cantidad = match ($c->fuenteCantidad) {
            FuenteComponente::Novedad => $this->novedad($c, $ctx),
            FuenteComponente::Parametro => $calc->parametro($c->codigoParametro ?? throw $this->config($c, 'codigo_parametro'), $ctx),
            default => throw $this->config($c, 'fuente_cantidad'),
        };
        $detalle['cantidad'] = (string) $cantidad;

        // --- valor unitario ---
        $recargo = BigDecimal::one();
        switch ($c->fuenteValor) {
            case FuenteComponente::Parametro:
                $valor = Importe::de($calc->parametro($c->codigoParametro ?? throw $this->config($c, 'codigo_parametro'), $ctx));
                $detalle['valor_unitario_origen'] = "parámetro {$c->codigoParametro}";
                break;
            case FuenteComponente::BaseZonda:
                $valor = $calc->horaNormal($c->codigoBaseZonda ?? throw $this->config($c, 'codigo_base_zonda'), $ctx);
                $detalle['valor_unitario_origen'] = "hora normal de {$c->codigoBaseZonda}";
                // recargo (1.5 / 2.0) desde el parámetro del concepto si lo tiene, si no factor_valor
                if ($c->fuenteCantidad === FuenteComponente::Novedad && $c->codigoParametro !== null) {
                    $recargo = $calc->parametro($c->codigoParametro, $ctx);
                    $detalle['recargo'] = "parámetro {$c->codigoParametro} = {$recargo}";
                }
                break;
            case FuenteComponente::Basico:
                $valor = $calc->basicoDiario($ctx);
                $detalle['valor_unitario_origen'] = 'básico diario (básico / DIVISOR_DIAS)';
                break;
            default:
                throw $this->config($c, 'fuente_valor');
        }
        if ($c->factorValor !== null) {
            $recargo = $recargo->multipliedBy($c->factorValor);
            $detalle['factor_valor'] = (string) $c->factorValor;
        }
        $importe = $valor->por($cantidad)->por($recargo);
        $detalle['valor_unitario'] = (string) $valor;
        $detalle['factor_total'] = (string) $recargo;

        return new ResultadoEstrategia($importe, $cantidad, $valor, $detalle);
    }

    private function novedad(Concepto $c, ContextoRelacionLaboral $ctx): BigDecimal
    {
        $codigo = $c->codigoNovedadCantidad ?? throw $this->config($c, 'codigo_novedad_cantidad');
        $cantidad = $ctx->cantidadNovedad($codigo);
        if ($cantidad === null) {
            if ($c->requiereNovedad) {
                throw MotorBloqueado::porque(CodigoBloqueo::NovedadObligatoriaAusente, "Novedad obligatoria '{$codigo}' ausente para {$c->codigo}: el motor bloquea.", "motor_novedades:{$codigo}", null);
            }
            throw new SinNovedad($codigo);
        }

        return $cantidad;
    }

    private function config(Concepto $c, string $campo): MotorBloqueado
    {
        return MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "{$c->codigo}: configuración de cálculo incompleta ({$campo}).", 'motor_conceptos', $campo);
    }
}
