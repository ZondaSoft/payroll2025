<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Orden de cálculo derivado de las dependencias. Port de construirGrafo() en
 * src/dsl_v11.js.
 *
 * El contrato es explícito: **no hay orden_calculo manual**, manda el grafo. Los
 * nodos son conceptos (`C:COD`) y bases (`B:COD`); las aristas salen de integrar
 * una base, de consumir otra con BASE(), de referenciar un concepto con CONC() y
 * de las dependencias que declara un módulo. PARAM, NOV, VAR y VALCAT no generan
 * arista porque no dependen de otro cálculo.
 *
 * El orden sale por Kahn, ordenando alfabéticamente a igual grado de entrada para
 * que una misma configuración produzca siempre la misma secuencia.
 */
final class GrafoCalculo
{
    public const CONCEPTO = 'C:';

    public const BASE = 'B:';

    /** @var array<string, list<string>> nodo => nodos que dependen de él */
    private array $aristas = [];

    /** @var array<string, true> */
    private array $nodos = [];

    public function __construct(private readonly ValidadorVersion $validador = new ValidadorVersion) {}

    /**
     * @param  list<VersionFormula>  $versiones
     * @param  list<string>  $conceptosAdicionales  conceptos sin versión que igual deben
     *                                              quedar en la corrida (el perfil los declara)
     * @return array{orden: list<string>, ciclo: bool, nodosEnCiclo: list<string>, aristas: array<string, list<string>>}
     */
    public function construir(array $versiones, CatalogoDsl $catalogos, array $conceptosAdicionales = []): array
    {
        $this->aristas = [];
        $this->nodos = [];

        foreach ($conceptosAdicionales as $codigo) {
            $this->nodos[self::CONCEPTO.$codigo] = true;
        }

        // Integrar una base es una arista concepto → base.
        foreach ($catalogos->bases as $codigoBase => $base) {
            $this->nodos[self::BASE.$codigoBase] = true;
            foreach ($base['integrantes'] ?? [] as $codigoConcepto) {
                $this->arista(self::CONCEPTO.$codigoConcepto, self::BASE.$codigoBase);
            }
        }

        foreach ($versiones as $version) {
            $destino = self::CONCEPTO.$version->codigoConcepto;
            $this->nodos[$destino] = true;

            foreach ($this->validador->validar($version, $catalogos)->aristas() as $d) {
                if ($d['tipo'] === 'CONCEPTO') {
                    $this->arista(self::CONCEPTO.$d['codigo'], $destino);
                }
                if ($d['tipo'] === 'BASE') {
                    $this->arista(self::BASE.$d['codigo'], $destino);
                }
                if ($d['tipo'] === 'MODULO') {
                    // Un módulo arrastra sus propias dependencias al grafo (§16).
                    foreach ($d['dependencias'] ?? [] as $dep) {
                        if (preg_match('/^(CONC|BASE)\(([A-Z0-9_]+)\)$/', $dep, $m) === 1) {
                            $this->arista(($m[1] === 'CONC' ? self::CONCEPTO : self::BASE).$m[2], $destino);
                        }
                    }
                }
            }
        }

        return $this->kahn();
    }

    private function arista(string $desde, string $hasta): void
    {
        $this->nodos[$desde] = true;
        $this->nodos[$hasta] = true;
        $this->aristas[$desde] ??= [];
        if (! in_array($hasta, $this->aristas[$desde], true)) {
            $this->aristas[$desde][] = $hasta;
        }
    }

    /** @return array{orden: list<string>, ciclo: bool, nodosEnCiclo: list<string>, aristas: array<string, list<string>>} */
    private function kahn(): array
    {
        $nodos = array_keys($this->nodos);
        $gradoEntrada = array_fill_keys($nodos, 0);
        foreach ($this->aristas as $destinos) {
            foreach ($destinos as $hasta) {
                $gradoEntrada[$hasta]++;
            }
        }

        $cola = array_values(array_filter($nodos, fn (string $n) => $gradoEntrada[$n] === 0));
        sort($cola);
        $orden = [];
        while ($cola !== []) {
            $n = array_shift($cola);
            $orden[] = $n;
            $siguientes = $this->aristas[$n] ?? [];
            sort($siguientes);
            foreach ($siguientes as $hasta) {
                if (--$gradoEntrada[$hasta] === 0) {
                    $cola[] = $hasta;
                }
            }
        }

        $ciclo = count($orden) !== count($nodos);

        return [
            'orden' => $orden,
            'ciclo' => $ciclo,
            'nodosEnCiclo' => $ciclo ? array_values(array_diff($nodos, $orden)) : [],
            'aristas' => $this->aristas,
        ];
    }
}
