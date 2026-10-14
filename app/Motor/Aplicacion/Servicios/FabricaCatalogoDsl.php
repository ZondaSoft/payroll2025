<?php

namespace App\Motor\Aplicacion\Servicios;

use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Aplicacion\Puertos\RepositorioValoresCategoria;
use App\Motor\Aplicacion\Puertos\RepositorioVariables;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Entidades\Parametro;
use App\Motor\Dominio\Entidades\TipoNovedad;
use App\Motor\Dominio\Entidades\Variable;
use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Modulos\RegistroModulos;
use DateTimeImmutable;

/**
 * Arma el catálogo que el DSL necesita para validar y evaluar: qué referencias
 * existen y con qué unidad.
 *
 * No trae valores — ésos los resuelve ResolvedorDominio para cada trabajador. Lo
 * que viaja acá es la declaración: sin unidad declarada el analizador no puede
 * verificar que una fórmula devuelva pesos, y lo dice con CFG_UNIDAD_SIN_DECLARAR
 * en vez de suponerla.
 */
final class FabricaCatalogoDsl
{
    public function __construct(
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioParametros $parametros,
        private readonly RepositorioTiposNovedad $novedades,
        private readonly RepositorioVariables $variables,
        private readonly RepositorioValoresCategoria $valoresCategoria,
        private readonly RepositorioBasesZonda $bases,
        private readonly RegistroModulos $modulos,
    ) {}

    public function construir(DateTimeImmutable $fecha): CatalogoDsl
    {
        return new CatalogoDsl(
            conceptos: $this->conceptosDeclarados(),
            parametros: $this->porCodigo($this->parametros->todos(),
                fn (Parametro $p) => $p->codigo, fn (Parametro $p) => ['unidad' => $p->unidad]),
            novedades: $this->porCodigo($this->novedades->todos(),
                fn (TipoNovedad $n) => $n->codigo,
                fn (TipoNovedad $n) => ['unidad' => $n->unidad, 'regla_agregacion' => $n->reglaAgregacion]),
            variables: $this->porCodigo($this->variables->catalogo(),
                fn (Variable $v) => $v->codigo, fn (Variable $v) => ['unidad' => $v->unidad]),
            valcat: $this->valcatDeclarado(),
            bases: $this->basesConIntegrantes($fecha),
            modulos: $this->modulos->paraCatalogo(),
        );
    }

    /** @return array<string, array{unidad_cantidad?: ?string}> */
    private function conceptosDeclarados(): array
    {
        $out = [];
        foreach ($this->conceptos->todos() as $c) {
            /** @var Concepto $c */
            $out[(string) $c->codigo] = ['unidad_cantidad' => $c->unidadPrincipal];
        }

        return $out;
    }

    /**
     * La unidad de VALCAT es la del concepto, no la de la categoría: el mismo
     * VALCAT(TOLHOR) son horas en todas las categorías que lo tengan.
     *
     * Sale de la declaración y no de los valores vigentes: que una categoría no
     * tenga valor cargado no vuelve inexistente la referencia — son dos problemas
     * distintos y el motor los informa distinto.
     *
     * @return array<string, array{unidad?: ?string}>
     */
    private function valcatDeclarado(): array
    {
        $out = [];
        foreach ($this->valoresCategoria->conceptosDeclarados() as $codigo => $unidad) {
            $out[$codigo] = ['unidad' => $unidad];
        }

        return $out;
    }

    /** @return array<string, array{integrantes: list<string>}> */
    private function basesConIntegrantes(DateTimeImmutable $fecha): array
    {
        $out = [];
        foreach ($this->bases->todas() as $b) {
            $out[$b->codigo] = ['integrantes' => []];
        }
        foreach ($this->bases->composicionVigente($fecha) as $codigoBase => $incidencias) {
            foreach ($incidencias as $i) {
                /** @var IncidenciaBaseZonda $i */
                if ($i->integra) {
                    $out[$codigoBase]['integrantes'][] = $i->codigoConcepto;
                }
            }
        }

        return $out;
    }

    /**
     * @template T
     *
     * @param  list<T>  $filas
     * @return array<string, array<string, mixed>>
     */
    private function porCodigo(array $filas, callable $codigo, callable $datos): array
    {
        $out = [];
        foreach ($filas as $f) {
            $out[$codigo($f)] = $datos($f);
        }

        return $out;
    }
}
