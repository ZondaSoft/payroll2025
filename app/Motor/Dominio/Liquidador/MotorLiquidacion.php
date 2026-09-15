<?php

namespace App\Motor\Dominio\Liquidador;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Estrategias\RegistroEstrategias;
use App\Motor\Dominio\Estrategias\SinNovedad;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\BaseImponibleResultado;
use App\Motor\Dominio\Liquidador\Resultado\Bloqueo;
use App\Motor\Dominio\Liquidador\Resultado\LineaAporte;
use App\Motor\Dominio\Liquidador\Resultado\LineaContribucion;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;
use App\Motor\Dominio\Servicios\ConstructorBasesArca;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\Redondeador;
use App\Motor\Dominio\Servicios\ResolutorAlicuotas;
use App\Motor\Dominio\Servicios\ResolutorAntiguedad;
use App\Motor\Dominio\Servicios\ResolutorAplicabilidad;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorElegibilidad;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use App\Motor\Dominio\Servicios\ResolutorRegimenes;
use App\Motor\Dominio\Servicios\ResolutorTopes;

/**
 * Orquestador de la liquidación de UNA relación laboral en un período (orden del
 * mockup resolverLiquidacion). Fail-closed con bloqueos ACUMULADOS: cada etapa
 * captura MotorBloqueado, lo registra y sigue con las etapas independientes;
 * los bloqueos fatales cortan el resto.
 *
 * Etapas: parámetros técnicos → versiones (snapshot) → aplicabilidad → elegibilidad
 *   → básico → haberes integrantes de BASE_HORA_NORMAL → consumidores → resto
 *   → bases ARCA → topes → aportes → contribuciones → totales.
 */
final class MotorLiquidacion
{
    public const BASE_HORA_NORMAL = 'BASE_HORA_NORMAL';

    private const PARAMETROS_TECNICOS = ['REDONDEO_DECIMALES', 'DIVISOR_DIAS', 'HORAS_MES_REFERENCIA'];

    public function __construct(
        private readonly RegistroEstrategias $estrategias = new RegistroEstrategias,
        private readonly ResolutorAplicabilidad $aplicabilidad = new ResolutorAplicabilidad,
        private readonly ResolutorElegibilidad $elegibilidad = new ResolutorElegibilidad,
        private readonly ResolutorAntiguedad $antiguedad = new ResolutorAntiguedad,
        private readonly ConstructorBasesArca $basesArca = new ConstructorBasesArca,
    ) {}

    /**
     * @param  list<\App\Motor\Dominio\Entidades\ValorParametro>  $valoresParametros  vigentes para los ámbitos del trabajador
     * @param  array<int, list<\App\Motor\Dominio\Entidades\Basico>>  $basicosPorCategoria
     */
    public function liquidar(ContextoPeriodo $periodo, ContextoRelacionLaboral $ctx, array $valoresParametros, array $basicosPorCategoria): Liquidacion
    {
        $liq = new Liquidacion($periodo->periodo, $ctx->relacion->id ?? 0, $periodo->tipoLiquidacion, $periodo->entorno);
        $gate = new GateValidacion($periodo->entorno);
        $parametros = new ResolutorParametros($valoresParametros, $gate);
        $calc = new EntornoCalculo($parametros, new ResolutorBasico($basicosPorCategoria, $gate), $this->antiguedad, $liq->traza);

        // 0) relación laboral válida
        if (! $ctx->relacion->estaVigenteEn($periodo->fechaResolucion) && $periodo->tipoLiquidacion->value !== 'FINAL') {
            $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::RelacionInconsistente, "La relación laboral #{$ctx->relacion->id} no está vigente al {$periodo->fechaResolucion->format('Y-m-d')} (estado {$ctx->relacion->estado->value}).", 'relacion', 'motor_relacion_laboral', 'estado', true));
        }

        // 1) parámetros técnicos (fatales)
        $decimales = 2;
        foreach (self::PARAMETROS_TECNICOS as $p) {
            try {
                $v = $parametros->resolver($p, $ctx, $liq->traza);
                if ($p === 'REDONDEO_DECIMALES') {
                    $decimales = $v->toInt();
                }
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::ParametroTecnicoAusente, "Parámetro técnico {$p}: {$e->getMessage()}", 'parametros_tecnicos', $e->entidad, $e->campo, true));
            }
        }
        $redondeador = new Redondeador($decimales);
        $liq->etapas['parametros_tecnicos'] = $liq->tieneBloqueoFatal() ? 'BLOQUEADA' : 'OK';
        if ($liq->tieneBloqueoFatal()) {
            return $this->cerrar($liq, $ctx);
        }

        // 2) versiones vigentes → conceptos + aplicabilidad + elegibilidad
        $activados = [];   // codigo => [Concepto, ConceptoVersion]
        $integrantes = []; // base zonda => conceptos
        $incidenciasArca = [];
        foreach ($periodo->versiones as $codigo => $version) {
            $c = $version->concepto();
            if (! $c->activo) {
                continue;
            }
            try {
                $decision = $this->aplicabilidad->decidir($codigo, $version->aplicabilidad(), $ctx);
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'aplicabilidad', $codigo));

                continue;
            }
            if (! $decision['aplica']) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => $decision['resultado'] === ResolutorAplicabilidad::SIN_REGLA ? 'sin regla de aplicabilidad coincidente' : 'excluido por regla '.$decision['regla']?->ambito->value];

                continue;
            }
            try {
                $el = $this->elegibilidad->evaluar($c, $periodo->tipoLiquidacion, $periodo->periodo);
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'elegibilidad', $codigo));

                continue;
            }
            if (! $el['elegible']) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => (string) $el['motivo']];

                continue;
            }
            $activados[$codigo] = [$c, $version];
            foreach ($version->incidenciasBaseZonda() as $ibz) {
                if ($ibz->integra && $ibz->vigencia->contiene($periodo->fechaResolucion)) {
                    $integrantes[$ibz->codigoBaseZonda][] = $codigo;
                }
            }
            $incidenciasArca[$codigo] = $version->incidenciasArca();
        }
        if ($liq->tieneBloqueoFatal()) {
            $liq->etapas['aplicabilidad'] = 'BLOQUEADA';

            return $this->cerrar($liq, $ctx);
        }
        $liq->etapas['aplicabilidad'] = 'OK';
        $calc->integrantesBases = $integrantes;
        // las bases de Zonda existen aunque ningún concepto activado las integre todavía
        foreach ($periodo->versiones as $version) {
            foreach ($version->incidenciasBaseZonda() as $ibz) {
                $calc->integrantesBases[$ibz->codigoBaseZonda] ??= [];
            }
        }

        // 3) haberes: primero integrantes de BASE_HORA_NORMAL, luego consumidores de bases, luego el resto
        $haberes = array_filter($activados, fn ($par) => $par[0]->esHaber() && ! $par[0]->esModuloSistema());
        $orden = $this->ordenar($haberes, $integrantes);
        $versionesUsadas = [];
        foreach ($orden as $codigo) {
            [$c, $version] = $activados[$codigo];
            try {
                $r = $this->estrategias->para($c)->calcular($c, $ctx, $calc);
                if ($r->manual) {
                    $liq->agregarAviso(new Aviso('MANUAL', "{$codigo} es MANUAL: requiere carga manual; no se ejecuta automáticamente.", 'motor_conceptos', $codigo));
                    $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => 'carga manual'];

                    continue;
                }
                $redondeado = $redondeador->redondear($r->importe, $c->reglaRedondeo);
                if ($redondeado->esCero() && ! $c->permiteImporteCero) {
                    $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => 'importe cero (no permitido)'];

                    continue;
                }
                $linea = new LineaHaber($c, $redondeado->por($c->coeficienteSigno() === 0 ? 1 : $c->coeficienteSigno()), $r->importe, $r->cantidad, $r->valorUnitario, $version->id, $version->numeroVersion, $r->detalle);
                $calc->haberes[] = $linea;
                $calc->invalidarBases();
                if ($c->tipo === TipoConcepto::Descuento) {
                    $liq->descuentos[] = $linea;
                } else {
                    $liq->haberes[] = $linea;
                }
                $versionesUsadas[] = $version->id;
                $liq->traza->registrar('haberes', "{$codigo} = {$linea->importe->aTexto(2)}", $r->detalle, $codigo);
            } catch (SinNovedad $e) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => "sin novedad {$e->codigoNovedad} en el período"];
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'haberes', $codigo));
            }
        }
        $liq->etapas['haberes'] = $liq->haberes === [] && $liq->tieneBloqueos() ? 'BLOQUEADA' : 'OK';

        // 4) bases de Zonda (resultado)
        foreach ($calc->integrantesBases as $codigoBase => $conceptos) {
            $b = new BaseImponibleResultado($codigoBase, 'ZONDA');
            foreach ($liq->haberes as $h) {
                in_array($h->codigo(), $conceptos, true) ? $b->incluir($h->codigo(), $h->importe) : $b->excluir($h->codigo(), 'no integra la base');
            }
            $liq->basesZonda[$codigoBase] = $b;
        }

        // 5) bases ARCA (solo haberes remunerativos/no remunerativos según incidencia)
        try {
            $r = $this->basesArca->construir($periodo->aplicacionesPorClave(), $incidenciasArca, $liq->haberes, $periodo->fechaResolucion, $periodo->entorno);
            $liq->basesArca = $r['bases'];
            foreach ($r['avisos'] as $a) {
                $liq->agregarAviso($a);
            }
            $liq->etapas['bases_arca'] = 'OK';
        } catch (MotorBloqueado $e) {
            $liq->agregarBloqueo(Bloqueo::desde($e, 'bases_arca'));
            $liq->etapas['bases_arca'] = 'BLOQUEADA';
        }

        // 6) topes (por base topeable, componente MENSUAL en esta fase)
        $topes = new ResolutorTopes($periodo->topes, $gate);
        foreach ($liq->basesArca as $codigoBase => $base) {
            $def = $periodo->baseArca($codigoBase);
            if ($def === null) {
                continue;
            }
            try {
                $t = $topes->aplicar($def, $base->importeAntesAjustes, ComponenteTope::Mensual);
                foreach ($t['avisos'] as $a) {
                    $liq->agregarAviso($a);
                }
                if ($t['maximo'] !== null && ! $t['importe']->igualA($base->importeAntesAjustes)) {
                    $base->aplicarTope($t['importe'], $t['importe']->igualA($t['maximo']->importe) ? $t['maximo']->codigo : ($t['minimo']?->codigo ?? '?'), $t['importe']->igualA($t['maximo']->importe) ? 'MAXIMO' : 'MINIMO');
                }
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'topes'));
                $liq->etapas['topes'] = 'BLOQUEADA';
            }
        }
        $liq->etapas['topes'] ??= 'OK';

        // 7) aportes personales (MODULO_SISTEMA con sujeto TRABAJADOR): alícuota por condición × base topeada
        $alicuotas = new ResolutorAlicuotas($periodo->alicuotasAportes, $gate);
        $condicion = $ctx->relacion->encuadreArca?->codigoCondicion ?? '1';
        foreach ($activados as $codigo => [$c, $version]) {
            if (! $c->esModuloSistema() || $c->tipo !== TipoConcepto::Descuento) {
                continue;
            }
            $subsistema = $this->subsistemaDeConcepto($periodo, $c);
            if ($subsistema === null || $c->baseModulo === null) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => 'descuento sin subsistema/base ARCA: lo resuelve un módulo externo (sindical, OS, etc.)'];

                continue;
            }
            if (! in_array($subsistema, $alicuotas->subsistemasConAporte(), true)) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => "aporte {$subsistema}: sin alícuota en ALICUOTAS_APORTES (lo resuelve el módulo de {$subsistema}: obra social/FSR)"];

                continue;
            }
            try {
                $r = $alicuotas->aporte($subsistema, $condicion);
                if ($r['aviso'] !== null) {
                    $liq->agregarAviso($r['aviso']);
                }
                $base = $liq->basesArca[$c->baseModulo] ?? null;
                if ($base === null) {
                    throw MotorBloqueado::porque(CodigoBloqueo::AlicuotaAusente, "{$codigo}: la base {$c->baseModulo} no fue construida.", 'motor_bases_arca');
                }
                $importe = $r['alicuota']->porcentaje->aplicarA($r['alicuota']->aplicaTope ? $base->importeAplicado : $base->importeAntesAjustes)->redondeado(2);
                $liq->aportes[] = new LineaAporte($codigo, $subsistema, $c->baseModulo, $base->importeAplicado, $r['alicuota']->porcentaje, $importe, $r['alicuota']->codigo, $r['alicuota']->codigoCondicion);
                $versionesUsadas[] = $version->id;
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'aportes', $codigo));
            }
        }
        $liq->etapas['aportes'] = 'OK';

        // 8) contribuciones patronales: encuadramiento A/B → regla BASE → SUSTITUTIVO → ADICIONAL → DESTINO
        try {
            if ($ctx->encuadramiento === null) {
                throw MotorBloqueado::porque(CodigoBloqueo::EncuadramientoAusente, "La empresa #{$ctx->relacion->empresaId} no tiene encuadramiento patronal (A/B) vigente al {$periodo->fechaResolucion->format('Y-m-d')}.", 'motor_empresa_encuadramiento');
            }
            $avisoEnc = $gate->evaluar($ctx->encuadramiento->estadoValidacion, 'motor_empresa_encuadramiento', "Encuadramiento {$ctx->encuadramiento->encuadramiento} de la empresa");
            if ($avisoEnc !== null) {
                $liq->agregarAviso($avisoEnc);
            }
            $regimenes = new ResolutorRegimenes($periodo->alicuotasContribuciones, $periodo->regimenes, $gate);
            $codigos = array_map(fn ($r) => $r->codigoRegimen, $ctx->relacion->regimenesVigentesA($periodo->fechaResolucion));
            $r = $regimenes->contribuciones($ctx->encuadramiento->encuadramiento, $codigos, $periodo->periodo, $ctx->relacion->fechaIngreso);
            foreach ($r['avisos'] as $a) {
                $liq->agregarAviso($a);
            }
            foreach ($r['lineas'] as $l) {
                $base = $liq->basesArca[$l['base']] ?? null;
                if ($base === null) {
                    $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::AlicuotaAusente, "Contribución {$l['subsistema']}: la base {$l['base']} no fue construida.", 'contribuciones', 'motor_bases_arca'));

                    continue;
                }
                $concepto = $this->conceptoContribucionPara($activados, $l['subsistema']);
                $liq->contribuciones[] = new LineaContribucion($concepto ?? 'CONT_'.$l['subsistema'], $l['subsistema'], $l['base'], $base->importeAplicado, $l['porcentaje'],
                    $l['porcentaje']->aplicarA($base->importeAplicado)->redondeado(2), $l['regla'], $l['regimen'], $ctx->encuadramiento->encuadramiento, $l['destino']);
            }
            $liq->etapas['contribuciones'] = 'OK';
        } catch (MotorBloqueado $e) {
            $liq->agregarBloqueo(Bloqueo::desde($e, 'contribuciones'));
            $liq->etapas['contribuciones'] = 'BLOQUEADA';
        }

        $liq->traza->registrar('resumen', 'Versiones usadas', ['ids' => array_values(array_unique($versionesUsadas))]);

        return $this->cerrar($liq, $ctx);
    }

    /**
     * Orden de ejecución: integrantes de BASE_HORA_NORMAL → conceptos que consumen una base → resto.
     * Un concepto que consume una base no puede integrarla (R13); si ocurre, se bloquea al calcular.
     *
     * @param  array<string, array{0: Concepto, 1: ConceptoVersion}>  $haberes
     * @param  array<string, list<string>>  $integrantes
     * @return list<string>
     */
    private function ordenar(array $haberes, array $integrantes): array
    {
        $integranHora = $integrantes[self::BASE_HORA_NORMAL] ?? [];
        $primero = $consumidores = $resto = [];
        foreach ($haberes as $codigo => [$c]) {
            if (in_array($codigo, $integranHora, true) && $c->codigoBaseZonda === null) {
                $primero[] = $codigo;
            } elseif ($c->codigoBaseZonda !== null) {
                $consumidores[] = $codigo;
            } else {
                $resto[] = $codigo;
            }
        }
        $porOrden = fn (string $a, string $b) => ($haberes[$a][0]->ordenCalculo ?? $haberes[$a][0]->ordenVisual ?? 999) <=> ($haberes[$b][0]->ordenCalculo ?? $haberes[$b][0]->ordenVisual ?? 999);
        usort($primero, $porOrden);
        usort($resto, $porOrden);
        usort($consumidores, $porOrden);
        // BASICO y ANTIG antes que los porcentajes sobre básico
        $esBase = fn (string $x) => in_array($haberes[$x][0]->estrategia, [EstrategiaCalculo::Basico, EstrategiaCalculo::Antiguedad], true) ? 0 : 1;
        usort($primero, fn ($a, $b) => $esBase($a) <=> $esBase($b) ?: $porOrden($a, $b));
        usort($resto, fn ($a, $b) => $esBase($a) <=> $esBase($b) ?: $porOrden($a, $b));

        return array_merge($primero, $resto, $consumidores);
    }

    private function subsistemaDeConcepto(ContextoPeriodo $periodo, Concepto $c): ?string
    {
        // APJUB → SIPA, APINSSJYP → INSSJP, APOSOC → OS: por base_modulo y subsistemas con esa base de aportes
        if ($c->baseModulo === null) {
            return null;
        }
        $candidatos = array_values(array_filter($periodo->subsistemas, fn ($s) => $s->baseAportes === $c->baseModulo));
        if (count($candidatos) === 1) {
            return $candidatos[0]->codigo;
        }
        foreach ($candidatos as $s) {
            if (str_contains(strtoupper((string) $c->codigo), str_replace(['INSSJP'], ['INSSJ'], $s->codigo))) {
                return $s->codigo;
            }
        }

        return $candidatos[0]->codigo ?? null;
    }

    /** @param array<string, array{0: Concepto, 1: ConceptoVersion}> $activados */
    private function conceptoContribucionPara(array $activados, string $subsistema): ?string
    {
        $mapa = ['SIPA' => 'CONTSS', 'INSSJP' => 'CONTSS', 'AAFF' => 'CONTSS', 'FNE' => 'CONTSS', 'OS' => 'CONTOS', 'FSR' => 'CONTOS', 'LRT' => 'CONTART', 'RENATRE' => 'CONTSS'];
        $codigo = $mapa[$subsistema] ?? null;

        return $codigo !== null && isset($activados[$codigo]) ? $codigo : null;
    }

    private function cerrar(Liquidacion $liq, ContextoRelacionLaboral $ctx): Liquidacion
    {
        foreach ($ctx->avisos as $a) {
            $liq->agregarAviso($a);
        }
        $ctx->avisos = [];

        return $liq;
    }
}
