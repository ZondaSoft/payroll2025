<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\BaseImponibleResultado;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use DateTimeInterface;

/**
 * Bases ARCA REM_1..REM_10 por aplicación: un concepto integra la REM_n de una
 * aplicación si su incidencia (del snapshot) es S. N excluye. PENDIENTE bloquea
 * en PRODUCCION y avisa en PRUEBA. La ausencia de fila NO equivale a N: se trata
 * como PENDIENTE (spec CONCEPTO_INCIDENCIA_ARCA).
 * Las bases se suman de líneas ya redondeadas.
 */
final class ConstructorBasesArca
{
    /**
     * @param  array<string, SubsistemaAplicacion>  $aplicaciones  por clave SUBSISTEMA|TIPO
     * @param  array<string, list<IncidenciaArca>>  $incidenciasPorConcepto  del snapshot de cada concepto
     * @param  list<LineaHaber>  $haberes
     * @return array{bases: array<string, BaseImponibleResultado>, avisos: list<Aviso>}
     */
    public function construir(array $aplicaciones, array $incidenciasPorConcepto, array $haberes, DateTimeInterface $fecha, Entorno $entorno): array
    {
        $bases = [];
        $avisos = [];
        foreach ($aplicaciones as $clave => $ap) {
            if ($ap->codigoBase === null || ! $ap->activo) {
                continue;
            }
            $base = $bases[$ap->codigoBase] ??= new BaseImponibleResultado($ap->codigoBase);
            foreach ($haberes as $h) {
                $codigo = $h->codigo();
                if (isset($base->incluidos[$codigo]) || isset($base->excluidos[$codigo])) {
                    continue; // varias aplicaciones comparten REM_n (SIPA y INSSJP → REM_2): se decide una vez por base
                }
                $inc = $this->incidenciaVigente($incidenciasPorConcepto[$codigo] ?? [], $clave, $fecha);
                if ($inc === Incidencia::Si) {
                    $base->incluir($codigo, $h->importe);
                } elseif ($inc === Incidencia::No) {
                    $base->excluir($codigo, 'incidencia N');
                } else {
                    if ($entorno === Entorno::Produccion) {
                        throw MotorBloqueado::porque(CodigoBloqueo::IncidenciaPendiente, "Incidencia ARCA de {$codigo} en {$clave} ({$ap->codigoBase}) PENDIENTE: en PRODUCCION el motor bloquea.", "motor_concepto_incidencia_arca:{$codigo}", 'incidencia');
                    }
                    $base->excluir($codigo, 'incidencia PENDIENTE (excluido con aviso, entorno PRUEBA)');
                    $avisos[] = new Aviso('INCIDENCIA_PENDIENTE', "Incidencia ARCA de {$codigo} en {$clave} ({$ap->codigoBase}) PENDIENTE: se excluye de la base en PRUEBA.", "motor_concepto_incidencia_arca:{$codigo}", $codigo);
                }
            }
        }

        return ['bases' => $bases, 'avisos' => $avisos];
    }

    /** @param list<IncidenciaArca> $incidencias */
    private function incidenciaVigente(array $incidencias, string $claveAplicacion, DateTimeInterface $fecha): Incidencia
    {
        foreach ($incidencias as $i) {
            if ($i->claveAplicacion() === $claveAplicacion && $i->vigencia->contiene($fecha)) {
                return $i->incidencia;
            }
        }

        return Incidencia::Pendiente;
    }
}
