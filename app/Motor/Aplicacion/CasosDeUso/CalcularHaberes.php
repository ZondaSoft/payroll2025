<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\Puertos\RepositorioBasicos;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Aplicacion\Servicios\ArmadorContextoRelacion;
use App\Motor\Aplicacion\Servicios\CalculadorHaberesDsl;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use App\Motor\Dominio\ValorObjetos\Periodo;
use RuntimeException;

/**
 * Calcula los haberes de un trabajador en un período evaluando las fórmulas
 * publicadas. Devuelve la corrida completa: cada concepto con su importe, su
 * estado y su traza, las bases armadas y el orden que resolvió el grafo.
 *
 * Es lo que necesita el simulador para responder las preguntas del gate: qué
 * entró, qué dio, de qué depende y con qué vigencia.
 */
final class CalcularHaberes
{
    public function __construct(
        private readonly RepositorioRelacionesLaborales $relaciones,
        private readonly RepositorioParametros $parametros,
        private readonly RepositorioBasicos $basicos,
        private readonly ArmadorContextoRelacion $armador,
        private readonly CalculadorHaberesDsl $calculador,
        private readonly GateValidacion $gate,
    ) {}

    public function ejecutar(int $relacionLaboralId, Periodo $periodo, int $diasLiquidados = 30): EstadoCorrida
    {
        $rl = $this->relaciones->porId($relacionLaboralId);
        if ($rl === null) {
            throw new RuntimeException("La relación laboral #{$relacionLaboralId} no existe.");
        }

        return $this->paraContexto($this->armador->armar($rl, $periodo, $diasLiquidados));
    }

    /** Variante para quien ya tiene el contexto armado (el liquidador completo). */
    public function paraContexto(ContextoRelacionLaboral $ctx): EstadoCorrida
    {
        $fecha = $ctx->fechaResolucion;

        return $this->calculador->calcular(
            $ctx,
            new ResolutorParametros($this->parametros->valoresVigentes($fecha, $this->armador->ambitos($ctx)), $this->gate),
            new ResolutorBasico(
                $ctx->idCategoria === null ? [] : $this->basicos->porCategorias([$ctx->idCategoria], $fecha),
                $this->gate,
            ),
        );
    }
}
