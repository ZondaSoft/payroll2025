<?php

namespace App\Motor\Dominio\Servicios;

/**
 * Circularidad entre bases de Zonda (mockup R13 + detectCircularidadBases):
 *  - directa: un concepto consume una base y a la vez la integra;
 *  - indirecta: un concepto que consume Bi integra Bj, y en cadena Bj … vuelve a Bi.
 */
final class DetectorCircularidad
{
    /**
     * @param  array<string, list<string>>  $integrantes  base => conceptos que la integran
     * @param  array<string, string>  $consume  concepto => base que consume
     * @return list<string> conceptos con circularidad directa
     */
    public function directas(array $integrantes, array $consume): array
    {
        $out = [];
        foreach ($consume as $concepto => $base) {
            if (in_array($concepto, $integrantes[$base] ?? [], true)) {
                $out[] = $concepto;
            }
        }

        return $out;
    }

    /**
     * Grafo: Bi → Bj si algún concepto consume Bi e integra Bj. Devuelve los ciclos (listas de bases).
     *
     * @param  array<string, list<string>>  $integrantes
     * @param  array<string, string>  $consume
     * @return list<list<string>>
     */
    public function ciclos(array $integrantes, array $consume): array
    {
        $grafo = [];
        foreach ($consume as $concepto => $baseConsumida) {
            foreach ($integrantes as $base => $conceptos) {
                if (in_array($concepto, $conceptos, true)) {
                    $grafo[$baseConsumida][$base] = true;
                }
            }
        }
        $ciclos = [];
        $visitados = [];
        foreach (array_keys($grafo) as $inicio) {
            $this->dfs($inicio, $grafo, [], $visitados, $ciclos);
        }

        return $ciclos;
    }

    /** @param array<string, array<string, true>> $grafo */
    private function dfs(string $nodo, array $grafo, array $camino, array &$visitados, array &$ciclos): void
    {
        if (in_array($nodo, $camino, true)) {
            $ciclo = array_slice($camino, (int) array_search($nodo, $camino, true));
            // el mismo ciclo puede descubrirse desde cualquiera de sus nodos: se normaliza rotándolo al menor
            $min = (int) array_search(min($ciclo), $ciclo, true);
            $ciclo = array_merge(array_slice($ciclo, $min), array_slice($ciclo, 0, $min));
            $ciclo[] = $ciclo[0];
            $clave = implode('>', $ciclo);
            if (! isset($visitados[$clave])) {
                $visitados[$clave] = true;
                $ciclos[] = $ciclo;
            }

            return;
        }
        $camino[] = $nodo;
        foreach (array_keys($grafo[$nodo] ?? []) as $siguiente) {
            $this->dfs($siguiente, $grafo, $camino, $visitados, $ciclos);
        }
    }
}
