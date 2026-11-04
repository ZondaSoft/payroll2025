<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\BaseImponibleResultado;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\ValorObjetos\Importe;
use DateTimeInterface;

/**
 * Bases ARCA REM_1..REM_9 desde la matriz S/N (CONCEPTO_INCIDENCIA_ARCA). Port de
 * componerBaseAplicacion() y persistirBasesArca() de sim/motor_contributivo.js.
 *
 * Reglas de seguridad (instructivo §11–§12, auditoría pre-entrega PE-2):
 *  - la ausencia de fila nunca equivale a N: el concepto calculado sin incidencia
 *    vigente bloquea la base con INCIDENCIA_AUSENTE, en PRUEBA y en PRODUCCION;
 *  - una fila que no dice S ni N (PENDIENTE) bloquea con INCIDENCIA_NO_RESUELTA;
 *  - PRODUCCION exige CONFIRMADO; PRUEBA admite SIN_CONFIRMAR con aviso;
 *  - un concepto BLOQUEADO con incidencia S bloquea la base con su propio código;
 *    con N no la afecta;
 *  - una aplicación REGIMEN sólo compone si un régimen de la relación la habilita;
 *  - si dos aplicaciones de la misma REM_n no coinciden, la base se bloquea.
 *
 * Una base bloqueada no se convierte en cero: queda BLOQUEADA y sin importe.
 * Los importes se suman exactos; el redondeo es del aporte o la contribución.
 */
final class ConstructorBasesArca
{
    /**
     * @param  array<string, SubsistemaAplicacion>  $aplicaciones  por clave SUBSISTEMA|TIPO
     * @param  array<string, list<IncidenciaArca>>  $incidenciasPorConcepto
     * @param  list<ConceptoEnBase>  $conceptos
     * @param  list<string>|null  $subsistemasPorRegimen  subsistemas que los regímenes de la relación habilitan;
     *                                                     null = no distinguir REGIMEN (camino heredado)
     * @return array{bases: array<string, BaseImponibleResultado>, avisos: list<Aviso>, omitidas: list<array{clave: string, codigo_base: string, motivo: string}>}
     */
    public function componer(
        array $aplicaciones,
        array $incidenciasPorConcepto,
        array $conceptos,
        DateTimeInterface $fecha,
        GateValidacion $gate,
        ?array $subsistemasPorRegimen = null,
    ): array {
        $bases = [];
        $avisos = [];
        $omitidas = [];

        foreach ($aplicaciones as $clave => $ap) {
            if ($ap->codigoBase === null || ! $ap->activo) {
                continue;
            }
            if ($subsistemasPorRegimen !== null && $ap->esPorRegimen() && ! in_array($ap->codigoSubsistema, $subsistemasPorRegimen, true)) {
                $omitidas[] = ['clave' => $clave, 'codigo_base' => $ap->codigoBase, 'motivo' => 'NO_APLICA_REGIMEN'];

                continue;
            }

            $comp = $this->componerAplicacion($clave, $ap, $incidenciasPorConcepto, $conceptos, $fecha, $gate, $avisos);

            $previa = $bases[$ap->codigoBase] ?? null;
            if ($previa === null) {
                $bases[$ap->codigoBase] = $comp;

                continue;
            }
            if (! $this->coinciden($previa, $comp)) {
                $previa->bloquear('BASE_ARCA_INCONSISTENTE_ENTRE_SUBSISTEMAS',
                    "{$ap->codigoBase}: la aplicación {$clave} compone un importe o un detalle distinto que otra aplicación de la misma base.");
            }
        }

        return ['bases' => $bases, 'avisos' => $avisos, 'omitidas' => $omitidas];
    }

    /**
     * Camino heredado: haberes calculados por las estrategias tipadas. Mismas reglas
     * de seguridad; sin distinguir aplicaciones REGIMEN.
     *
     * @param  array<string, SubsistemaAplicacion>  $aplicaciones
     * @param  array<string, list<IncidenciaArca>>  $incidenciasPorConcepto
     * @param  list<LineaHaber>  $haberes
     * @return array{bases: array<string, BaseImponibleResultado>, avisos: list<Aviso>, omitidas: list<array{clave: string, codigo_base: string, motivo: string}>}
     */
    public function construir(array $aplicaciones, array $incidenciasPorConcepto, array $haberes, DateTimeInterface $fecha, Entorno $entorno): array
    {
        $conceptos = array_map(fn (LineaHaber $h) => ConceptoEnBase::calculado($h->codigo(), $h->importe), $haberes);

        return $this->componer($aplicaciones, $incidenciasPorConcepto, $conceptos, $fecha, new GateValidacion($entorno));
    }

    /**
     * @param  array<string, list<IncidenciaArca>>  $incidenciasPorConcepto
     * @param  list<ConceptoEnBase>  $conceptos
     * @param  list<Aviso>  $avisos
     */
    private function componerAplicacion(
        string $clave,
        SubsistemaAplicacion $ap,
        array $incidenciasPorConcepto,
        array $conceptos,
        DateTimeInterface $fecha,
        GateValidacion $gate,
        array &$avisos,
    ): BaseImponibleResultado {
        $base = new BaseImponibleResultado((string) $ap->codigoBase);

        try {
            $aviso = $gate->evaluar($ap->estadoValidacion, "motor_subsistema_aplicacion:{$clave}", "Aplicación {$clave}");
            if ($aviso !== null) {
                $avisos[] = $aviso;
            }
        } catch (MotorBloqueado $e) {
            $base->bloquear($e->codigo->value, $e->getMessage());
        }

        foreach ($conceptos as $c) {
            $fila = $this->incidenciaVigente($incidenciasPorConcepto[$c->codigo] ?? [], $clave, $fecha);
            $contexto = "{$clave} ({$ap->codigoBase}) · {$c->codigo}";

            if ($fila === null) {
                $base->bloquear('INCIDENCIA_AUSENTE', "{$contexto}: el concepto no tiene incidencia ARCA vigente; la ausencia no equivale a N.");

                continue;
            }
            if ($fila->incidencia !== Incidencia::Si && $fila->incidencia !== Incidencia::No) {
                $base->bloquear('INCIDENCIA_NO_RESUELTA', "{$contexto}: la incidencia está {$fila->incidencia->value}, no es S ni N.");

                continue;
            }

            if (! $c->calculado) {
                // PE-2: un concepto bloqueado sólo afecta las bases que integra.
                if ($fila->incidencia === Incidencia::Si) {
                    $base->bloquear($c->codigoError ?? 'CONC_BLOQUEADO', "{$contexto}: el concepto está bloqueado e integra la base.");
                }

                continue;
            }

            try {
                $aviso = $gate->evaluar($fila->estadoValidacion, "motor_concepto_incidencia_arca:{$c->codigo}", "Incidencia de {$contexto}", true, $c->codigo);
                if ($aviso !== null) {
                    $avisos[] = $aviso;
                }
            } catch (MotorBloqueado $e) {
                $base->bloquear($e->codigo->value, $e->getMessage());

                continue;
            }

            $importe = $c->importe ?? Importe::cero();
            if ($fila->incidencia === Incidencia::Si) {
                $base->incluir($c->codigo, $importe);
                $base->registrarDetalle($c->codigo, 'S', $importe, $importe);
            } else {
                $base->excluir($c->codigo, 'incidencia N');
                $base->registrarDetalle($c->codigo, 'N', $importe, Importe::cero());
            }
        }

        return $base;
    }

    private function coinciden(BaseImponibleResultado $a, BaseImponibleResultado $b): bool
    {
        return $a->estado === $b->estado
            && $a->importeAntesAjustes->igualA($b->importeAntesAjustes)
            && $a->detalle === $b->detalle;
    }

    /** @param list<IncidenciaArca> $incidencias */
    private function incidenciaVigente(array $incidencias, string $claveAplicacion, DateTimeInterface $fecha): ?IncidenciaArca
    {
        foreach ($incidencias as $i) {
            if ($i->claveAplicacion() === $claveAplicacion && $i->vigencia->contiene($fecha)) {
                return $i;
            }
        }

        return null;
    }
}
