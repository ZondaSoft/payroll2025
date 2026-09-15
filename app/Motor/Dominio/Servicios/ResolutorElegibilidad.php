<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Enums\TipoRepeticion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\ValorObjetos\Periodo;

/**
 * Elegibilidad de un concepto incluido: tipo de liquidación permitido y
 * periodicidad (mockup resolverElegibilidadConcepto + resolverPeriodicidad).
 */
final class ResolutorElegibilidad
{
    private const MESES = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

    /** @return array{elegible: bool, motivo: ?string} */
    public function evaluar(Concepto $c, TipoLiquidacion $tipo, Periodo $periodo): array
    {
        if (! $c->admiteTipoLiquidacion($tipo)) {
            return ['elegible' => false, 'motivo' => "no permitido en liquidación {$tipo->value}"];
        }
        $p = $this->periodicidad($c, $periodo);
        if (! $p['cumple']) {
            return ['elegible' => false, 'motivo' => $p['motivo']];
        }

        return ['elegible' => true, 'motivo' => null];
    }

    /** @return array{cumple: bool, motivo: ?string} */
    public function periodicidad(Concepto $c, Periodo $periodo): array
    {
        $tr = $c->tipoRepeticion;
        if ($tr === null || $tr === TipoRepeticion::TodosLosMeses || $tr === TipoRepeticion::SinRestriccion) {
            return ['cumple' => true, 'motivo' => null];
        }
        if ($tr === TipoRepeticion::MesesEspecificos) {
            $mes = self::MESES[$periodo->mes - 1];
            $lista = array_map('strtoupper', $c->mesesAplicacion);
            if ($lista === []) {
                throw MotorBloqueado::porque(CodigoBloqueo::PeriodicidadDesconocida, "{$c->codigo}: MESES_ESPECIFICOS sin meses configurados (error de configuración).", 'motor_conceptos', 'meses_aplicacion');
            }

            return in_array($mes, $lista, true) ? ['cumple' => true, 'motivo' => null] : ['cumple' => false, 'motivo' => 'solo se liquida en '.implode(', ', $lista)];
        }
        if ($c->periodoInicio === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::PeriodicidadDesconocida, "{$c->codigo}: {$tr->value} sin período de inicio (error de configuración).", 'motor_conceptos', 'periodo_inicio');
        }
        $meses = $periodo->mesesDesde($c->periodoInicio);
        if ($meses < 0) {
            return ['cumple' => false, 'motivo' => "empieza en {$c->periodoInicio}"];
        }
        if ($tr === TipoRepeticion::CadaNMeses) {
            $n = (int) $c->intervaloMeses;
            if ($n <= 0) {
                throw MotorBloqueado::porque(CodigoBloqueo::PeriodicidadDesconocida, "{$c->codigo}: CADA_N_MESES exige un intervalo mayor a cero.", 'motor_conceptos', 'intervalo_meses');
            }

            return $meses % $n === 0 ? ['cumple' => true, 'motivo' => null] : ['cumple' => false, 'motivo' => "se liquida cada {$n} meses desde {$c->periodoInicio}"];
        }
        // DURANTE_N_MESES
        $n = (int) $c->cantidadMeses;
        if ($n <= 0) {
            throw MotorBloqueado::porque(CodigoBloqueo::PeriodicidadDesconocida, "{$c->codigo}: DURANTE_N_MESES exige una cantidad mayor a cero.", 'motor_conceptos', 'cantidad_meses');
        }

        return $meses < $n ? ['cumple' => true, 'motivo' => null] : ['cumple' => false, 'motivo' => "se liquidó durante {$n} meses desde {$c->periodoInicio}; ya venció"];
    }
}
