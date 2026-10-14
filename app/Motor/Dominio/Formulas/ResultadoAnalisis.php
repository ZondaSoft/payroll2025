<?php

namespace App\Motor\Dominio\Formulas;

/** Lo que el analizador devuelve: el árbol, los problemas, las dependencias y la unidad resultante. */
final class ResultadoAnalisis
{
    /** @var list<array{codigo: string, mensaje: string, pos?: int}> */
    public array $problemas = [];

    /** @var list<array{tipo: string, codigo: string, arista: bool, dependencias?: list<string>}> */
    public array $dependencias = [];

    /** @var list<array{valor: string, pos: int}> */
    public array $literales = [];

    /** @var list<string> referencias cuya unidad el catálogo no declara */
    public array $sinUnidad = [];

    public ?Dimension $unidad = null;

    public function __construct(public ?Nodo $ast = null) {}

    public function problema(string $codigo, string $mensaje, ?int $pos = null): void
    {
        $this->problemas[] = $pos === null ? ['codigo' => $codigo, 'mensaje' => $mensaje] : ['codigo' => $codigo, 'mensaje' => $mensaje, 'pos' => $pos];
    }

    /** @return list<string> */
    public function codigosDeProblema(): array
    {
        return array_values(array_unique(array_column($this->problemas, 'codigo')));
    }

    public function tiene(string $codigo): bool
    {
        return in_array($codigo, $this->codigosDeProblema(), true);
    }

    /** @return list<array{tipo: string, codigo: string}> dependencias que generan arista en el grafo */
    public function aristas(): array
    {
        return array_values(array_filter($this->dependencias, fn (array $d) => $d['arista']));
    }
}
