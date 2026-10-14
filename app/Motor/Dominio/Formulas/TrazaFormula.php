<?php

namespace App\Motor\Dominio\Formulas;

/** Qué resolvió el evaluador y con qué valor: es la respuesta a "de dónde salió este importe". */
final class TrazaFormula
{
    /** @var list<array<string, mixed>> */
    public array $referencias = [];

    /** @var list<string> */
    public array $literales = [];

    /** @param array<string, mixed> $datos */
    public function referencia(string $ref, array $datos): void
    {
        $this->referencias[] = ['ref' => $ref] + $datos;
    }

    public function literal(string $texto): void
    {
        $this->literales[] = $texto;
    }

    /** @return list<string> */
    public function resumen(): array
    {
        return array_map(
            fn (array $r) => $r['ref'].' = '.(is_scalar($r['valor'] ?? null) ? (string) $r['valor'] : (string) ($r['valor'] ?? '—')),
            $this->referencias
        );
    }
}
