<?php

namespace App\Motor\Dominio\Formulas;

use App\Motor\Dominio\Entidades\ValorCategoriaConcepto;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Modulos\RegistroModulos;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use App\Motor\Dominio\Servicios\ResolutorVariables;
use Brick\Math\BigDecimal;

/**
 * Traduce las referencias del DSL a los datos del trabajador. Es el único punto
 * donde el lenguaje se encuentra con el modelo: cada VAR, PARAM, NOV o VALCAT
 * sale de la tabla que el contrato le asignó, y no de la que esté a mano.
 *
 * Lo importante es de dónde NO salen las cosas: BASCAT sale de la escala de
 * básicos y no de `sue001s.bruto`; HSPACT sale de la jornada pactada del
 * trabajador y no se infiere de la categoría ni del convenio; HMJORN y DIVJOR
 * salen de los divisores normativos del convenio y no de dividir uno por otro.
 *
 * CONC y BASE no se resuelven acá: los sirve ResolvedorConEstado desde la corrida
 * en curso, porque dependen del orden que fija el grafo.
 */
final class ResolvedorDominio implements ResolvedorDsl
{
    /** Variables que resuelve el contexto del trabajador, no la tabla de valores. */
    public const DEL_CONTEXTO = ['BASCAT', 'HSPACT', 'DIASLIQ', 'ANTANIO'];

    /** @var array<string, list<ValorCategoriaConcepto>> */
    private array $valcatPorConcepto = [];

    /** @param list<ValorCategoriaConcepto> $valoresCategoria */
    public function __construct(
        private readonly ContextoRelacionLaboral $ctx,
        private readonly ResolutorParametros $parametros,
        private readonly ResolutorVariables $variables,
        private readonly ResolutorBasico $basico,
        private readonly RegistroModulos $modulos,
        array $valoresCategoria = [],
    ) {
        foreach ($valoresCategoria as $v) {
            $this->valcatPorConcepto[$v->codigoConcepto][] = $v;
        }
    }

    public function parametro(string $codigo): array
    {
        return ['valor' => $this->sinBloqueo('PARAM_SIN_VALOR', fn () => $this->parametros->resolver($codigo, $this->ctx))];
    }

    /**
     * Una novedad ausente no es un error: se informa que no existe y la versión
     * decide (REQUERIDA ⇒ el concepto no aplica; OPCIONAL ⇒ cero tipado).
     */
    public function novedad(string $codigo): array
    {
        $cantidad = $this->ctx->cantidadNovedad($codigo);

        return $cantidad === null ? ['existe' => false] : ['existe' => true, 'valor' => $cantidad];
    }

    public function variable(string $codigo): array
    {
        return ['valor' => match ($codigo) {
            'BASCAT' => $this->sinBloqueo('VAR_SIN_VALOR', fn () => $this->basico->resolver($this->ctx)->valor),
            'HSPACT' => $this->horasPactadas(),
            'DIASLIQ' => BigDecimal::of($this->ctx->diasLiquidados),
            'ANTANIO' => BigDecimal::of($this->ctx->aniosAntiguedad()),
            default => $this->sinBloqueo('VAR_SIN_VALOR', fn () => $this->variables->resolver($codigo, $this->ctx)),
        }];
    }

    public function valcat(string $codigo): array
    {
        if ($this->ctx->idCategoria === null) {
            throw BloqueoDsl::porque('VALCAT_SIN_VALOR',
                "VALCAT({$codigo}): la relación laboral no tiene categoría vigente al ".$this->ctx->fechaResolucion->format('Y-m-d'));
        }
        $candidatos = array_values(array_filter(
            $this->valcatPorConcepto[$codigo] ?? [],
            fn (ValorCategoriaConcepto $v) => $v->activo
                && $v->idCategoria === $this->ctx->idCategoria
                && $v->vigencia->contiene($this->ctx->fechaResolucion)
        ));
        if ($candidatos === []) {
            throw BloqueoDsl::porque('VALCAT_SIN_VALOR',
                "VALCAT({$codigo}): sin valor para la categoría {$this->ctx->codigoCategoria} al ".$this->ctx->fechaResolucion->format('Y-m-d'));
        }
        if (count($candidatos) > 1) {
            throw BloqueoDsl::porque('VALCAT_SIN_VALOR',
                "VALCAT({$codigo}): dos valores vigentes para la categoría {$this->ctx->codigoCategoria} (configuración ambigua)");
        }

        return ['valor' => $candidatos[0]->valor, 'categoria' => $this->ctx->idCategoria,
            'vigencia' => (string) $candidatos[0]->vigencia];
    }

    public function concepto(string $codigo): BigDecimal
    {
        throw BloqueoDsl::porque('CONC_BLOQUEADO',
            "CONC({$codigo}) sólo se resuelve dentro de una corrida: falta el estado de cálculo");
    }

    public function base(string $codigo): BigDecimal
    {
        throw BloqueoDsl::porque('BASE_BLOQUEADA',
            "BASE({$codigo}) sólo se resuelve dentro de una corrida: falta el estado de cálculo");
    }

    public function modulo(string $codigo): array
    {
        $modulo = $this->sinBloqueoDe('MOD_SIN_RESULTADO', fn () => $this->modulos->obtener($codigo));

        return [
            'valor' => $this->sinBloqueo('MOD_SIN_RESULTADO', fn () => $modulo->resolver($this->ctx)),
            'version' => 'v1',
        ];
    }

    /**
     * En larga distancia la jornada pactada no existe y la fórmula no la usa: si
     * alguien la pide y no está, es un dato faltante, no un cero.
     */
    private function horasPactadas(): BigDecimal
    {
        $horas = $this->ctx->jornada->horasSemanales;
        if ($this->ctx->jornada->sinDato || $horas === null || $horas <= 0) {
            throw BloqueoDsl::porque('VAR_SIN_VALOR',
                'VAR(HSPACT): la relación laboral no tiene jornada pactada informada y no se infiere');
        }

        return BigDecimal::of($horas);
    }

    /** Los bloqueos del dominio hablan el idioma del motor; el DSL necesita el suyo. */
    private function sinBloqueo(string $codigo, callable $fn): BigDecimal
    {
        try {
            return $fn();
        } catch (MotorBloqueado $e) {
            throw BloqueoDsl::porque($codigo, $e->getMessage());
        }
    }

    private function sinBloqueoDe(string $codigo, callable $fn): mixed
    {
        try {
            return $fn();
        } catch (MotorBloqueado $e) {
            throw BloqueoDsl::porque($codigo, $e->getMessage());
        }
    }
}
