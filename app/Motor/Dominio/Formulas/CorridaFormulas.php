<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Una corrida completa: elige la versión de cada concepto según el perfil, deriva
 * el orden del grafo y lo recorre evaluando conceptos y armando bases. Port de
 * liquidar() en src/dsl_v11.js.
 *
 * Nada de esto conoce las etapas del liquidador: el orden sale de las
 * dependencias, y un concepto que llega sin poder resolverse queda registrado con
 * su causa en vez de valer cero.
 */
final class CorridaFormulas
{
    public function __construct(
        private readonly EvaluadorConcepto $evaluador = new EvaluadorConcepto,
        private readonly GrafoCalculo $grafo = new GrafoCalculo,
    ) {}

    /**
     * @param  list<VersionFormula>  $versiones  todas las vigentes, con sus variantes
     * @param  array<string, DecisionPerfil>  $decisiones  concepto => decisión del perfil
     */
    public function ejecutar(array $versiones, CatalogoDsl $catalogos, ResolvedorDsl $datos, array $decisiones = []): EstadoCorrida
    {
        $estado = new EstadoCorrida;
        $resolvedor = new ResolvedorConEstado($datos, $estado);

        [$elegidas, $conflictos] = $this->elegirVersiones($versiones, $decisiones);
        $g = $this->grafo->construir(array_values($elegidas), $catalogos, array_keys($decisiones));
        $estado->orden = $g['orden'];

        if ($g['ciclo']) {
            // Un ciclo no se puede resolver parcialmente: todo lo que participa queda
            // bloqueado con el ciclo como causa, y lo demás se liquida igual.
            foreach ($g['nodosEnCiclo'] as $nodo) {
                if (str_starts_with($nodo, GrafoCalculo::CONCEPTO)) {
                    $codigo = substr($nodo, 2);
                    $estado->conceptos[$codigo] = ResultadoConcepto::bloqueado($codigo, 'DSL_CICLO_DEPENDENCIAS',
                        'El concepto participa de un ciclo de dependencias: '.implode(', ', $g['nodosEnCiclo']));
                } else {
                    $estado->bases[substr($nodo, 2)] = BaseConstruida::enCiclo(substr($nodo, 2), $g['nodosEnCiclo']);
                }
            }
        }

        foreach ($g['orden'] as $nodo) {
            $codigo = substr($nodo, 2);

            if (str_starts_with($nodo, GrafoCalculo::BASE)) {
                $estado->bases[$codigo] = BaseConstruida::armar(
                    $codigo, $catalogos->bases[$codigo]['integrantes'] ?? [], $estado->conceptos
                );

                continue;
            }

            if (isset($conflictos[$codigo])) {
                $estado->conceptos[$codigo] = ResultadoConcepto::bloqueado($codigo, 'ERROR_CONFIGURACION', $conflictos[$codigo]);

                continue;
            }

            $decision = $decisiones[$codigo] ?? DecisionPerfil::aplica();

            // Un perfil ambiguo o ausente no se resuelve calculando: se informa.
            if ($decision->estaBloqueada()) {
                $estado->conceptos[$codigo] = ResultadoConcepto::bloqueado(
                    $codigo, (string) $decision->codigoBloqueo, (string) $decision->motivo
                );

                continue;
            }

            $version = $elegidas[$codigo] ?? null;

            if ($version === null) {
                $estado->conceptos[$codigo] = $decision->aplica
                    ? ResultadoConcepto::noModelado($codigo, 'VERSION')
                    : ResultadoConcepto::noAplica($codigo, $decision->motivo ?? 'no aplica según el perfil');

                continue;
            }

            $estado->conceptos[$codigo] = $this->evaluador->evaluar(
                $version, $catalogos, $resolvedor, $decision->aplica, $decision->motivo
            );
        }

        return $estado;
    }

    /**
     * Una versión por concepto: la de la variante que indicó el perfil. Un concepto
     * con varias variantes y sin variante elegida es un error de configuración, no
     * una elección implícita de la primera.
     *
     * @param  list<VersionFormula>  $versiones
     * @param  array<string, DecisionPerfil>  $decisiones
     * @return array{0: array<string, VersionFormula>, 1: array<string, string>}
     */
    private function elegirVersiones(array $versiones, array $decisiones): array
    {
        /** @var array<string, list<VersionFormula>> $porConcepto */
        $porConcepto = [];
        foreach ($versiones as $v) {
            $porConcepto[$v->codigoConcepto][] = $v;
        }

        $elegidas = [];
        $conflictos = [];
        foreach ($porConcepto as $codigo => $candidatas) {
            $variante = $decisiones[$codigo]->variante ?? null;

            if ($variante !== null) {
                $coincide = array_values(array_filter($candidatas, fn (VersionFormula $v) => $v->variante === $variante));
                if ($coincide === []) {
                    $conflictos[$codigo] = "El perfil pide la variante {$variante} y el concepto no la tiene publicada";

                    continue;
                }
                $elegidas[$codigo] = $coincide[0];

                continue;
            }

            if (count($candidatas) === 1) {
                $elegidas[$codigo] = $candidatas[0];

                continue;
            }

            $variantes = implode(', ', array_map(fn (VersionFormula $v) => $v->variante, $candidatas));
            $conflictos[$codigo] = "El concepto tiene varias variantes ({$variantes}) y el perfil no eligió ninguna";
        }

        return [$elegidas, $conflictos];
    }
}
