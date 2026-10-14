<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;

/** Lo que se va calculando en una corrida: conceptos resueltos y bases armadas. */
final class EstadoCorrida
{
    /** @var array<string, ResultadoConcepto> */
    public array $conceptos = [];

    /** @var array<string, BaseConstruida> */
    public array $bases = [];

    /** @var list<string> orden en que el grafo resolvió los nodos */
    public array $orden = [];

    public function concepto(string $codigo): ?ResultadoConcepto
    {
        return $this->conceptos[$codigo] ?? null;
    }

    public function base(string $codigo): ?BaseConstruida
    {
        return $this->bases[$codigo] ?? null;
    }

    /** Suma de los conceptos calculados que impactan el neto (§17). */
    public function neto(): BigDecimal
    {
        $total = BigDecimal::zero();
        foreach ($this->conceptos as $r) {
            if ($r->esCalculado() && $r->impactaNeto) {
                $total = $total->plus($r->importeResultado);
            }
        }

        return $total;
    }

    /**
     * Suma los conceptos calculados de un grupo. Los subtotales del recibo —
     * remunerativo, no remunerativo, descuentos — son grupos de conceptos, y
     * agruparlos es de quien conoce la naturaleza de cada uno, no del DSL.
     *
     * @param  list<string>  $codigos
     */
    public function sumaDe(array $codigos): BigDecimal
    {
        $total = BigDecimal::zero();
        foreach ($codigos as $codigo) {
            $r = $this->conceptos[$codigo] ?? null;
            if ($r !== null && $r->esCalculado()) {
                $total = $total->plus($r->importeResultado);
            }
        }

        return $total;
    }

    /** @return array<string, ResultadoConcepto> */
    public function porEstado(string $estado): array
    {
        return array_filter($this->conceptos, fn (ResultadoConcepto $r) => $r->estado === $estado);
    }
}
