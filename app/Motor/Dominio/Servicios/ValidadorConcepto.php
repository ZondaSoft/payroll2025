<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\ModoCalculo;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\NivelValidacion;
use App\Motor\Dominio\Enums\Signo;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoRepeticion;

/**
 * Las 20 validaciones del mockup (VALIDACIONES_20) + reglas R2/R3/R5/R7/R8/R9/R10/R13.
 * Devuelve una lista de {codigo, nivel, mensaje, campo}; ERROR impide publicar,
 * AVISO se muestra pero no bloquea.
 */
final class ValidadorConcepto
{
    public const RANGO_AFIP = ['REMUNERATIVO' => [110000, 499999], 'NO_REMUNERATIVO' => [510000, 799999], 'DESCUENTO' => [810000, 829999]];

    /** Marcas obligatorias para un REMUNERATIVO (R10): todas salvo regímenes diferenciales/especiales. */
    public const APLICACIONES_OBLIGATORIAS_REM = ['SIPA|APORTE', 'SIPA|CONTRIBUCION', 'INSSJP|APORTE', 'INSSJP|CONTRIBUCION', 'OS|APORTE', 'OS|CONTRIBUCION',
        'FSR|APORTE', 'FSR|CONTRIBUCION', 'AAFF|CONTRIBUCION', 'FNE|CONTRIBUCION', 'LRT|CONTRIBUCION'];

    /**
     * @param  list<IncidenciaArca>  $incidenciasArca
     * @param  list<IncidenciaBaseZonda>  $basesZonda
     * @param  array<string, list<string>>  $composicionBases  base => conceptos integrantes (para circularidad indirecta)
     * @param  array<string, string>  $consumos  concepto => base que consume
     * @param  list<string>  $codigosExistentes  otros códigos de concepto (unicidad)
     * @param  list<string>  $parametrosExistentes
     * @param  list<string>  $tiposNovedadExistentes
     * @param  list<string>  $basesZondaExistentes
     * @return list<array{codigo: string, nivel: string, mensaje: string, campo: ?string}>
     */
    public function validar(Concepto $c, array $incidenciasArca = [], array $basesZonda = [], array $composicionBases = [], array $consumos = [],
        array $codigosExistentes = [], array $parametrosExistentes = [], array $tiposNovedadExistentes = [], array $basesZondaExistentes = []): array
    {
        $r = [];
        $err = function (string $codigo, string $msg, ?string $campo = null) use (&$r): void {
            $r[] = ['codigo' => $codigo, 'nivel' => NivelValidacion::Error->value, 'mensaje' => $msg, 'campo' => $campo];
        };
        $aviso = function (string $codigo, string $msg, ?string $campo = null) use (&$r): void {
            $r[] = ['codigo' => $codigo, 'nivel' => NivelValidacion::Aviso->value, 'mensaje' => $msg, 'campo' => $campo];
        };
        $codigo = (string) $c->codigo;

        // V1 / R8 — código único
        if (in_array($codigo, $codigosExistentes, true)) {
            $err('V01', "Ya existe un concepto con el código {$codigo} (R8: unicidad).", 'codigo_concepto');
        }
        // V2 — activo sin configuración de cálculo
        if ($c->activo && $c->estrategia === null) {
            $err('V02', 'Un concepto activo debe tener una estrategia de cálculo.', 'estrategia_calculo');
        }
        // V8 — concepto de sistema sin módulo responsable
        if ($c->esConceptoSistema && ($c->moduloResponsable === null || $c->moduloResponsable === '')) {
            $err('V08', 'Un concepto de sistema debe declarar su módulo responsable.', 'modulo_responsable');
        }
        // V9 — manual sin autorización de edición
        if ($c->fuenteImporte === FuenteComponente::Manual && ! $c->editableManual) {
            $err('V09', 'Un concepto con importe MANUAL debe permitir edición manual.', 'editable_manual');
        }
        // V13 / V14 / V17 — referencias
        if ($c->codigoNovedadCantidad !== null && $tiposNovedadExistentes !== [] && ! in_array($c->codigoNovedadCantidad, $tiposNovedadExistentes, true)) {
            $err('V13', "La novedad {$c->codigoNovedadCantidad} no existe.", 'codigo_novedad_cantidad');
        }
        if ($c->codigoParametro !== null && $parametrosExistentes !== [] && ! in_array($c->codigoParametro, $parametrosExistentes, true)) {
            $err('V14', "El parámetro {$c->codigoParametro} no existe.", 'codigo_parametro');
        }
        if ($c->codigoBaseZonda !== null && $basesZondaExistentes !== [] && ! in_array($c->codigoBaseZonda, $basesZondaExistentes, true)) {
            $err('V17', "La base de Zonda {$c->codigoBaseZonda} no existe.", 'codigo_base_zonda');
        }
        // REF — coherencia estrategia ↔ fuentes
        if ($c->estrategia === EstrategiaCalculo::CantidadPorValor) {
            if ($c->fuenteCantidad === FuenteComponente::Novedad && $c->codigoNovedadCantidad === null) {
                $err('REF', 'CANTIDAD_X_VALOR por novedad exige codigo_novedad_cantidad.', 'codigo_novedad_cantidad');
            }
            if ($c->fuenteValor === FuenteComponente::Parametro && $c->codigoParametro === null) {
                $err('REF', 'CANTIDAD_X_VALOR con valor por parámetro exige codigo_parametro.', 'codigo_parametro');
            }
            if ($c->fuenteValor === FuenteComponente::BaseZonda && $c->codigoBaseZonda === null) {
                $err('REF', 'CANTIDAD_X_VALOR con valor por hora normal exige codigo_base_zonda.', 'codigo_base_zonda');
            }
            if ($c->fuenteCantidad === null || $c->fuenteValor === null) {
                $err('V18', 'CANTIDAD_X_VALOR exige fuente de cantidad y fuente de valor.', 'fuente_cantidad');
            }
        }
        if ($c->estrategia === EstrategiaCalculo::PorcentajeSobreBase && $c->codigoParametro === null) {
            $err('REF', 'PORCENTAJE_SOBRE_BASE exige un parámetro con el porcentaje.', 'codigo_parametro');
        }
        if ($c->modoCalculo === ModoCalculo::ModuloSistema && $c->estrategia !== EstrategiaCalculo::ModuloSistema) {
            $err('V02', 'Un concepto MODULO_SISTEMA debe usar la estrategia MODULO_SISTEMA.', 'estrategia_calculo');
        }
        // R5 / V11 — mapeo ARCA por naturaleza
        if ($c->codigoAfip !== null) {
            $rango = self::RANGO_AFIP[$c->naturaleza->value] ?? null;
            if ($rango === null) {
                $err('V11', 'Este tipo de concepto no lleva código ARCA (no se exporta al LSD).', 'codigo_afip');
            } elseif ($c->codigoAfip < $rango[0] || $c->codigoAfip > $rango[1]) {
                $err('V11', "R5: el código ARCA {$c->codigoAfip} no corresponde al rango de {$c->naturaleza->value} ({$rango[0]}–{$rango[1]}).", 'codigo_afip');
            }
        } elseif (in_array($c->naturaleza, [Naturaleza::Remunerativo, Naturaleza::NoRemunerativo], true) && $c->esHaber()) {
            $aviso('V11', 'R5: falta el código ARCA; el concepto no se exportará al LSD.', 'codigo_afip');
        }
        // R10 — remunerativo debe integrar todas las bases
        if ($c->esRemunerativo() && $c->esHaber()) {
            $marcadas = [];
            foreach ($incidenciasArca as $i) {
                if ($i->incidencia === Incidencia::Si) {
                    $marcadas[] = $i->claveAplicacion();
                }
            }
            $faltan = array_values(array_diff(self::APLICACIONES_OBLIGATORIAS_REM, $marcadas));
            if ($faltan !== [] && $incidenciasArca !== []) {
                $aviso('R10', 'Un concepto REMUNERATIVO debería integrar todas las bases (salvo regímenes diferenciales/especiales). Faltan: '.implode(', ', $faltan).'.', 'incidencias_arca');
            }
        }
        // R13 / V6 / V7 — circularidad
        if ($c->codigoBaseZonda !== null) {
            foreach ($basesZonda as $bz) {
                if ($bz->integra && $bz->codigoBaseZonda === $c->codigoBaseZonda) {
                    $err('V06', "R13: el concepto consume la base {$c->codigoBaseZonda} y a la vez la integra (dependencia circular directa).", 'codigo_base_zonda');
                    break;
                }
            }
            $comp = $composicionBases;
            foreach ($basesZonda as $bz) {
                if ($bz->integra) {
                    $comp[$bz->codigoBaseZonda][] = $codigo;
                }
            }
            $ciclos = (new DetectorCircularidad)->ciclos($comp, $consumos + [$codigo => $c->codigoBaseZonda]);
            foreach ($ciclos as $ciclo) {
                if (in_array($c->codigoBaseZonda, $ciclo, true)) {
                    $err('V07', 'Circularidad indirecta entre bases: '.implode(' → ', $ciclo).'.', 'codigo_base_zonda');
                    break;
                }
            }
        }
        // R7 — periodicidad coherente
        $tr = $c->tipoRepeticion;
        $tieneCampos = $c->intervaloMeses !== null || $c->cantidadMeses !== null || $c->mesesAplicacion !== [] || $c->periodoInicio !== null;
        if (($tr === null || $tr === TipoRepeticion::TodosLosMeses || $tr === TipoRepeticion::SinRestriccion) && $tieneCampos) {
            $err('R07', 'Sin restricción periódica no deben cargarse intervalo, cantidad, meses ni período de inicio.', 'tipo_repeticion');
        }
        if ($tr === TipoRepeticion::CadaNMeses && (($c->intervaloMeses ?? 0) <= 0 || $c->periodoInicio === null)) {
            $err('R07', 'CADA_N_MESES exige un intervalo mayor a cero y un período de inicio.', 'intervalo_meses');
        }
        if ($tr === TipoRepeticion::DuranteNMeses && (($c->cantidadMeses ?? 0) <= 0 || $c->periodoInicio === null)) {
            $err('R07', 'DURANTE_N_MESES exige una cantidad mayor a cero y un período de inicio.', 'cantidad_meses');
        }
        if ($tr === TipoRepeticion::MesesEspecificos && $c->mesesAplicacion === []) {
            $err('R07', 'MESES_ESPECIFICOS exige indicar los meses.', 'meses_aplicacion');
        }
        // R9 — vigencia (la construcción de Vigencia ya rechaza hasta < desde; acá solo la fecha de inicio razonable)
        if ($c->vigencia->desde->format('Y') < '2000') {
            $err('R09', 'Vigencia desde inválida.', 'vigencia_desde');
        }
        // R2 / R3 — clasificación
        if ($c->activo && $c->clasificacionFuncional === 'PENDIENTE_CLASIFICAR') {
            $err('R02', 'Un concepto activo no puede quedar en PENDIENTE_CLASIFICAR.', 'clasificacion_funcional');
        }
        if ($c->clasificacionFuncional === 'AUSENTISMO' && ! in_array($c->ausentismoEvitable, ['EVITABLE', 'NO_EVITABLE'], true)) {
            $err('R03', 'La clasificación AUSENTISMO exige EVITABLE o NO_EVITABLE.', 'ausentismo_evitable');
        }
        if ($c->clasificacionFuncional !== 'AUSENTISMO' && $c->ausentismoEvitable !== null && $c->ausentismoEvitable !== 'NA') {
            $err('R03', "'ausentismo_evitable' solo aplica a la clasificación AUSENTISMO.", 'ausentismo_evitable');
        }
        // avisos de comportamiento (mockup)
        if ($c->tipo === TipoConcepto::Haber && $c->signo === Signo::Debito) {
            $aviso('SIGNO', 'HABER con signo DÉBITO: válido (inasistencia, suspensión). Verificá que sea intencional.', 'signo');
        }
        if ($c->requiereNovedad && $c->permiteImporteCero) {
            $aviso('CERO', 'Requiere novedad + permite importe cero: puede generar líneas vacías en el recibo.', 'permite_importe_cero');
        }
        if ($c->repetible && ($tr === null || $tr === TipoRepeticion::SinRestriccion)) {
            $aviso('REP', 'Repetible sin periodicidad: se liquidará solo cuando haya novedad.', 'repetible');
        }

        return $r;
    }

    /** @param list<array{nivel: string}> $resultados */
    public function tieneErrores(array $resultados): bool
    {
        foreach ($resultados as $x) {
            if ($x['nivel'] === NivelValidacion::Error->value) {
                return true;
            }
        }

        return false;
    }
}
