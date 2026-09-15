<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;

/**
 * Resultado completo de liquidar una relación laboral en un período. Se construye
 * incrementalmente por MotorLiquidacion y acumula bloqueos en vez de abortar en el primero.
 */
final class Liquidacion
{
    /** @var list<LineaHaber> */
    public array $haberes = [];

    /** @var list<LineaHaber> descuentos no contributivos (p. ej. cuota sindical) */
    public array $descuentos = [];

    /** @var array<string, BaseImponibleResultado> */
    public array $basesZonda = [];

    /** @var array<string, BaseImponibleResultado> REM_1..REM_10 */
    public array $basesArca = [];

    /** @var list<LineaAporte> */
    public array $aportes = [];

    /** @var list<LineaContribucion> */
    public array $contribuciones = [];

    /** @var list<Aviso> */
    public array $avisos = [];

    /** @var list<Bloqueo> */
    public array $bloqueos = [];

    /** @var list<array{codigo: string, motivo: string}> conceptos no ejecutados con motivo */
    public array $noEjecutados = [];

    public Traza $traza;

    /** @var array<string, string> etapa => OK | OMITIDA | BLOQUEADA */
    public array $etapas = [];

    public function __construct(
        public readonly Periodo $periodo,
        public readonly int $relacionLaboralId,
        public readonly TipoLiquidacion $tipoLiquidacion,
        public readonly Entorno $entorno,
    ) {
        $this->traza = new Traza;
    }

    public function agregarBloqueo(Bloqueo $b): void
    {
        $this->bloqueos[] = $b;
    }

    public function agregarAviso(Aviso $a): void
    {
        $this->avisos[] = $a;
    }

    public function tieneBloqueos(): bool
    {
        return $this->bloqueos !== [];
    }

    public function tieneBloqueoFatal(): bool
    {
        foreach ($this->bloqueos as $b) {
            if ($b->fatal) {
                return true;
            }
        }

        return false;
    }

    public function estado(): string
    {
        if ($this->tieneBloqueos()) {
            return 'BLOQUEADO';
        }

        return $this->avisos === [] ? 'OK' : 'CON_AVISOS';
    }

    public function totalHaberes(): Importe
    {
        return $this->sumar($this->haberes, fn (LineaHaber $l) => $l->concepto->tipo === TipoConcepto::Haber);
    }

    public function totalRemunerativo(): Importe
    {
        return $this->sumar($this->haberes, fn (LineaHaber $l) => $l->concepto->naturaleza === Naturaleza::Remunerativo);
    }

    public function totalNoRemunerativo(): Importe
    {
        return $this->sumar($this->haberes, fn (LineaHaber $l) => $l->concepto->naturaleza === Naturaleza::NoRemunerativo);
    }

    public function totalAportes(): Importe
    {
        $t = Importe::cero();
        foreach ($this->aportes as $a) {
            if (! $a->tecnico) {
                $t = $t->mas($a->importe);
            }
        }

        return $t;
    }

    public function totalDescuentos(): Importe
    {
        $t = $this->totalAportes();
        foreach ($this->descuentos as $d) {
            $t = $t->mas($d->importe->negado());
        }

        return $t;
    }

    public function neto(): Importe
    {
        return $this->totalHaberes()->menos($this->totalDescuentos());
    }

    public function totalContribuciones(): Importe
    {
        $t = Importe::cero();
        foreach ($this->contribuciones as $c) {
            if (! $c->tecnico) {
                $t = $t->mas($c->importe);
            }
        }

        return $t;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'periodo' => (string) $this->periodo, 'relacion_laboral_id' => $this->relacionLaboralId,
            'tipo_liquidacion' => $this->tipoLiquidacion->value, 'entorno' => $this->entorno->value, 'estado' => $this->estado(),
            'etapas' => $this->etapas,
            'haberes' => array_map(fn (LineaHaber $l) => $l->aArray(), $this->haberes),
            'descuentos' => array_map(fn (LineaHaber $l) => $l->aArray(), $this->descuentos),
            'bases_zonda' => array_map(fn (BaseImponibleResultado $b) => $b->aArray(), $this->basesZonda),
            'bases_arca' => array_map(fn (BaseImponibleResultado $b) => $b->aArray(), $this->basesArca),
            'aportes' => array_map(fn (LineaAporte $a) => $a->aArray(), $this->aportes),
            'contribuciones' => array_map(fn (LineaContribucion $c) => $c->aArray(), $this->contribuciones),
            'no_ejecutados' => $this->noEjecutados,
            'avisos' => array_map(fn (Aviso $a) => $a->aArray(), $this->avisos),
            'bloqueos' => array_map(fn (Bloqueo $b) => $b->aArray(), $this->bloqueos),
            'totales' => [
                'haberes' => $this->totalHaberes()->aTexto(2), 'remunerativo' => $this->totalRemunerativo()->aTexto(2),
                'no_remunerativo' => $this->totalNoRemunerativo()->aTexto(2), 'aportes' => $this->totalAportes()->aTexto(2),
                'descuentos' => $this->totalDescuentos()->aTexto(2), 'neto' => $this->neto()->aTexto(2),
                'contribuciones' => $this->totalContribuciones()->aTexto(2),
            ],
            'traza' => $this->traza->aArray(),
        ];
    }

    /** @param list<LineaHaber> $lineas */
    private function sumar(array $lineas, callable $filtro): Importe
    {
        $t = Importe::cero();
        foreach ($lineas as $l) {
            if ($filtro($l)) {
                $t = $t->mas($l->importe);
            }
        }

        return $t;
    }
}
