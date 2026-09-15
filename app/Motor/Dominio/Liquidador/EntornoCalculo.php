<?php

namespace App\Motor\Dominio\Liquidador;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Liquidador\Resultado\Traza;
use App\Motor\Dominio\Servicios\ResolutorAntiguedad;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use App\Motor\Dominio\ValorObjetos\Importe;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Servicios que las estrategias necesitan durante una liquidación: parámetros,
 * básico, bases de Zonda ya construidas (memoizadas) y hora normal.
 * La hora normal = BASE(BASE_HORA_NORMAL) / horas mensuales de la jornada
 * (o HORAS_MES_REFERENCIA si la jornada no está informada) — mockup/spec BASES_ZONDA.
 */
final class EntornoCalculo
{
    /** @var array<string, Importe> */
    private array $basesZonda = [];

    /** @var array<string, Importe> */
    private array $horaNormal = [];

    private ?Importe $basico = null;

    /** @var array<string, list<string>> base => conceptos integrantes vigentes */
    public array $integrantesBases = [];

    /** @var list<LineaHaber> haberes ya ejecutados (con importe redondeado) */
    public array $haberes = [];

    public function __construct(
        public readonly ResolutorParametros $parametros,
        public readonly ResolutorBasico $resolutorBasico,
        public readonly ResolutorAntiguedad $resolutorAntiguedad,
        public readonly Traza $traza,
    ) {}

    public function parametro(string $codigo, ContextoRelacionLaboral $ctx): BigDecimal
    {
        return $this->parametros->resolver($codigo, $ctx, $this->traza);
    }

    public function basico(ContextoRelacionLaboral $ctx): Importe
    {
        return $this->basico ??= $this->resolutorBasico->resolver($ctx);
    }

    /** básico × días / DIVISOR_DIAS × factor de jornada. */
    public function basicoDevengado(ContextoRelacionLaboral $ctx): Importe
    {
        $divisor = $this->parametro('DIVISOR_DIAS', $ctx);

        return $this->basico($ctx)->por($ctx->diasLiquidados)->dividido($divisor)->por($ctx->factor());
    }

    public function basicoDiario(ContextoRelacionLaboral $ctx): Importe
    {
        return $this->basico($ctx)->dividido($this->parametro('DIVISOR_DIAS', $ctx));
    }

    /** Base de Zonda = suma de los haberes ya ejecutados que la integran (memoizada por corrida). */
    public function baseZonda(string $codigoBase, ContextoRelacionLaboral $ctx): Importe
    {
        if (isset($this->basesZonda[$codigoBase])) {
            return $this->basesZonda[$codigoBase];
        }
        $integrantes = $this->integrantesBases[$codigoBase] ?? null;
        if ($integrantes === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::BaseZondaInexistente, "La base {$codigoBase} no existe, no está activa o no tiene composición vigente.", 'motor_bases_zonda', 'codigo_base');
        }
        $total = Importe::cero();
        $incluidos = [];
        foreach ($this->haberes as $h) {
            if (in_array($h->codigo(), $integrantes, true)) {
                $total = $total->mas($h->importe);
                $incluidos[] = $h->codigo();
            }
        }
        $this->traza->registrar('bases_zonda', "Base {$codigoBase} = ".$total->aTexto(2), ['integrantes' => $incluidos]);

        return $this->basesZonda[$codigoBase] = $total;
    }

    /** Hora normal = base / horas mensuales (jornada o HORAS_MES_REFERENCIA con aviso). */
    public function horaNormal(string $codigoBase, ContextoRelacionLaboral $ctx): Importe
    {
        if (isset($this->horaNormal[$codigoBase])) {
            return $this->horaNormal[$codigoBase];
        }
        $horas = $ctx->jornada->horasMensuales();
        $origen = "jornada {$ctx->jornada->descripcion} ({$ctx->jornada->horasSemanales} hs/sem)";
        if ($horas === null) {
            $horas = $this->parametro('HORAS_MES_REFERENCIA', $ctx);
            $origen = 'HORAS_MES_REFERENCIA (jornada no informada)';
            $ctx->avisos[] = new Aviso('JORNADA_SIN_DATO', "Jornada no informada para la relación #{$ctx->relacion->id}: se usa HORAS_MES_REFERENCIA = {$horas}.", 'sue001s.jornada_id');
        }
        if ($horas->isZero()) {
            throw MotorBloqueado::porque(CodigoBloqueo::JornadaSinDato, 'Horas mensuales en cero: no se puede calcular la hora normal.', 'motor_parametros:HORAS_MES_REFERENCIA');
        }
        $base = $this->baseZonda($codigoBase, $ctx);
        $hora = $base->dividido($horas->toScale(6, RoundingMode::HALF_UP));
        $this->traza->registrar('hora_normal', "Hora normal = {$base->aTexto(2)} / {$horas} = {$hora->aTexto(6)}", ['base' => $codigoBase, 'horas' => (string) $horas, 'origen' => $origen]);

        return $this->horaNormal[$codigoBase] = $hora;
    }

    public function invalidarBases(): void
    {
        $this->basesZonda = [];
        $this->horaNormal = [];
    }
}
