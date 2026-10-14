<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Evalúa un concepto completo: su secuencia de componentes y el cierre con
 * redondeo y signo. Port de evaluarConcepto() y finalizar() en src/dsl_v11.js.
 *
 * El orden del contrato (§6) es: aplicabilidad → novedad requerida → condición →
 * cantidad → valor unitario → importe → redondeo → signo → impacto en el neto.
 * Cada paso puede terminar en NO_APLICA o BLOQUEADO, y la diferencia importa: un
 * concepto que no corresponde al trabajador no es lo mismo que uno cuyo dato falta.
 */
final class EvaluadorConcepto
{
    public function __construct(private readonly Parser $parser = new Parser) {}

    /**
     * @param  bool  $aplica  lo decide Perfiles, fuera del DSL
     */
    public function evaluar(
        VersionFormula $version,
        CatalogoDsl $catalogos,
        ResolvedorDsl $resolvedor,
        bool $aplica = true,
        ?string $motivoNoAplica = null,
    ): ResultadoConcepto {
        $codigo = $version->codigoConcepto;
        $traza = new TrazaFormula;

        // 1. aplicabilidad
        if (! $aplica) {
            return ResultadoConcepto::noAplica($codigo, $motivoNoAplica ?? 'no aplica según el perfil', $version->variante, $traza);
        }

        // 2. novedades REQUERIDAS: si falta, el concepto no aplica — no es un bloqueo.
        //    Las OPCIONALES ausentes las resuelve el evaluador como cero tipado.
        foreach ($version->novedadesRequeridas() as $codigoNovedad) {
            try {
                $n = $resolvedor->novedad($codigoNovedad);
            } catch (BloqueoDsl $e) {
                return ResultadoConcepto::bloqueado($codigo, $e->codigo, $e->getMessage(), $version->variante, $traza);
            }
            if (! ($n['existe'] ?? false)) {
                return ResultadoConcepto::noAplica($codigo, "sin novedad {$codigoNovedad}", $version->variante, $traza);
            }
        }

        $evaluador = new Evaluador($catalogos, $resolvedor, $traza);

        // 3. método MODULO: el importe lo produce el módulo responsable, no una fórmula.
        if ($version->esModulo()) {
            try {
                $m = $resolvedor->modulo((string) $version->codigoModulo);
            } catch (BloqueoDsl $e) {
                return ResultadoConcepto::bloqueado($codigo, $e->codigo, $e->getMessage(), $version->variante, $traza);
            }
            $traza->referencia("MOD({$version->codigoModulo})", ['valor' => (string) $m['valor']]);

            return $this->cerrar($version, $m['valor'], null, null, $traza);
        }

        try {
            // 4. condición
            if ($version->tieneCondicion()) {
                $cumple = $evaluador->condicion($this->parser->parsear((string) $version->exprCondicion));
                if (! $cumple) {
                    return ResultadoConcepto::noAplica($codigo, 'la condición de la fórmula no se cumple', $version->variante, $traza);
                }
            }

            // 5-7. cantidad, valor unitario e importe, en ese orden: el importe usa
            //      CANT y VALUNI ya resueltos.
            $reservadas = [];
            $cantidad = null;
            $valorUnitario = null;
            if ($version->tieneCantidad()) {
                $cantidad = $evaluador->evaluar($this->parser->parsear((string) $version->exprCantidad), $reservadas);
                $reservadas['CANT'] = $cantidad;
            }
            if ($version->tieneValor()) {
                $valorUnitario = $evaluador->evaluar($this->parser->parsear((string) $version->exprValorUnitario), $reservadas);
                $reservadas['VALUNI'] = $valorUnitario;
            }
            $importe = $evaluador->evaluar($this->parser->parsear((string) $version->exprImporte), $reservadas);

            return $this->cerrar($version, $importe, $cantidad, $valorUnitario, $traza);
        } catch (BloqueoDsl $e) {
            return ResultadoConcepto::bloqueado($codigo, $e->codigo, $e->getMessage(), $version->variante, $traza);
        } catch (ErrorDsl $e) {
            return ResultadoConcepto::bloqueado($codigo, $e->codigo, $e->getMessage(), $version->variante, $traza);
        }
    }

    /** Redondeo propio de la versión, factor de signo e impacto en el neto (§17). */
    private function cerrar(
        VersionFormula $version, BigDecimal $bruto, ?BigDecimal $cantidad, ?BigDecimal $valorUnitario, TrazaFormula $traza
    ): ResultadoConcepto {
        $formula = match ($version->reglaRedondeo) {
            'DECIMAL' => $bruto->toScale(2, RoundingMode::HALF_UP),
            'ENTERO' => $bruto->toScale(0, RoundingMode::HALF_UP),
            default => $bruto,
        };

        return ResultadoConcepto::calculado($version->codigoConcepto, $bruto, $formula,
            $version->factorSigno(), $version->impactaNeto(), $cantidad, $valorUnitario, $version->variante, $traza);
    }
}
