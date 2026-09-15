<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Servicios\ValidadorConcepto;

/** Valida un concepto (guardado o no) contra el catálogo real: unicidad, referencias, circularidad, R-reglas. */
final class ValidarConcepto
{
    public function __construct(
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly RepositorioBasesZonda $basesZonda,
        private readonly RepositorioAplicabilidad $aplicabilidad,
        private readonly RepositorioParametros $parametros,
        private readonly RepositorioTiposNovedad $tiposNovedad,
        private readonly Reloj $reloj,
        private readonly ValidadorConcepto $validador = new ValidadorConcepto,
    ) {}

    /** @return list<array{codigo: string, nivel: string, mensaje: string, campo: ?string}> */
    public function ejecutar(Concepto $c): array
    {
        $codigo = (string) $c->codigo;
        $otros = [];
        $consumos = [];
        foreach ($this->conceptos->todos() as $x) {
            if ($x->id !== $c->id) {
                $otros[] = (string) $x->codigo;
            }
            if ($x->codigoBaseZonda !== null && $x->id !== $c->id) {
                $consumos[(string) $x->codigo] = $x->codigoBaseZonda;
            }
        }
        $composicion = [];
        foreach ($this->basesZonda->composicionVigente($this->reloj->hoy()) as $base => $lista) {
            $composicion[$base] = array_map(fn ($i) => $i->codigoConcepto, array_filter($lista, fn ($i) => $i->integra && $i->codigoConcepto !== $codigo));
        }

        return $this->validador->validar($c,
            $c->id === null ? [] : $this->incidencias->deConcepto($codigo),
            $c->id === null ? [] : $this->basesZonda->incidenciasDeConcepto($codigo),
            $composicion, $consumos, $otros,
            array_map(fn ($p) => $p->codigo, $this->parametros->todos()),
            array_map(fn ($t) => $t->codigo, $this->tiposNovedad->todos()),
            array_map(fn ($b) => $b->codigo, $this->basesZonda->todas()));
    }
}
