<?php

namespace App\Motor\Dominio\Liquidador;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Estrategias\RegistroEstrategias;
use App\Motor\Dominio\Estrategias\SinNovedad;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\BaseImponibleResultado;
use App\Motor\Dominio\Liquidador\Resultado\Bloqueo;
use App\Motor\Dominio\Liquidador\Resultado\LineaAporte;
use App\Motor\Dominio\Liquidador\Resultado\LineaContribucion;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;
use App\Motor\Dominio\Servicios\ConceptoEnBase;
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
use App\Motor\Dominio\ValorObjetos\Importe;

/**
 * Orquestador de la liquidación de UNA relación laboral en un período (orden del
 * mockup resolverLiquidacion). Fail-closed con bloqueos ACUMULADOS: cada etapa
 * captura MotorBloqueado, lo registra y sigue con las etapas independientes;
 * los bloqueos fatales cortan el resto.
 *
 * Etapas: parámetros técnicos → versiones (snapshot) → aplicabilidad → elegibilidad
 *   → básico → haberes integrantes de BASE_HORA_NORMAL → consumidores → resto
 *   → bases ARCA → topes → aportes → contribuciones → totales.
 *
 * Con una corrida de fórmulas ($haberesDsl) los haberes salen de ella —perfil,
 * grafo y DSL— y no de las estrategias tipadas: las bases ARCA se componen desde
 * los conceptos CALCULADOS y BLOQUEADOS de esa corrida contra la matriz S/N
 * vigente, y los aportes desde las aplicaciones APORTE de Seguridad Social
 * (instructivo §22 F–I; sim/motor_contributivo.js). Es el camino del simulador;
 * el de las estrategias queda para quien no trae corrida.
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
    /**
     * @param  array<string, list<IncidenciaArca>>|null  $incidenciasVigentes  matriz S/N vigente por concepto; requerida con $haberesDsl
     */
    public function liquidar(
        ContextoPeriodo $periodo,
        ContextoRelacionLaboral $ctx,
        array $valoresParametros,
        array $basicosPorCategoria,
        ?EstadoCorrida $haberesDsl = null,
        ?array $incidenciasVigentes = null,
    ): Liquidacion {
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

        $porFormulas = $haberesDsl !== null;
        $activados = [];   // codigo => [Concepto, ConceptoVersion]
        $versionesUsadas = [];
        $incidenciasArca = [];
        $conceptosEnBase = [];
        if ($porFormulas) {
            // 2–4) perfil + grafo + DSL ya resueltos en la corrida
            $conceptosEnBase = $this->haberesDesdeFormulas($liq, $periodo, $haberesDsl);
        } else {
        // 2) versiones vigentes → conceptos + aplicabilidad + elegibilidad
        $integrantes = []; // base zonda => conceptos
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
        }

        // 5) bases ARCA desde la matriz S/N. Una base que no se puede afirmar queda
        // BLOQUEADA y sin importe: nunca cero (PE-2, instructivo §11–§12).
        $r = $porFormulas
            ? $this->basesArca->componer($periodo->aplicacionesPorClave(), $incidenciasVigentes ?? [],
                $conceptosEnBase, $periodo->fechaResolucion, $gate, $this->subsistemasPorRegimen($periodo, $ctx))
            : $this->basesArca->construir($periodo->aplicacionesPorClave(), $incidenciasArca, $liq->haberes, $periodo->fechaResolucion, $periodo->entorno);
        $liq->basesArca = $r['bases'];
        foreach ($r['avisos'] as $a) {
            $liq->agregarAviso($a);
        }
        foreach ($r['omitidas'] as $o) {
            $liq->noEjecutados[] = ['codigo' => $o['clave'], 'motivo' => "aplicación por régimen ({$o['codigo_base']}): ningún régimen de la relación la habilita"];
        }
        foreach ($liq->basesArca as $codigoBase => $base) {
            if ($base->estaBloqueada()) {
                $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::BaseBloqueada, "{$codigoBase} [{$base->codigoError}]: {$base->motivoBloqueo}", 'bases_arca', 'motor_concepto_incidencia_arca'));
            }
        }
        $liq->etapas['bases_arca'] = array_filter($liq->basesArca, fn ($b) => $b->estaBloqueada()) === [] ? 'OK' : 'BLOQUEADA';

        // 5-bis) diferenciales de base ajenos a la matriz de conceptos: los resuelve el
        // módulo responsable (obra social por jornada parcial) y entran a la base ANTES
        // de los topes, porque lo topeable es bruto + diferencial.
        foreach ($ctx->diferencialesBase as $codigoBase => $reglas) {
            $base = $liq->basesArca[$codigoBase] ?? null;
            if ($base === null) {
                $liq->agregarAviso(new Aviso('DIFERENCIAL_SIN_BASE', "Diferencial para {$codigoBase}: la base no fue construida en esta corrida.", 'motor_reglas_base_diferencial_arca'));

                continue;
            }
            foreach ($reglas as $codigoRegla => $importe) {
                $base->agregarDiferencial(Importe::de((string) $importe), (string) $codigoRegla);
            }
        }

        // 6) topes (por base topeable, componente MENSUAL en esta fase)
        $topes = new ResolutorTopes($periodo->topes, $gate);
        foreach ($liq->basesArca as $codigoBase => $base) {
            $def = $periodo->baseArca($codigoBase);
            if ($def === null || $base->estaBloqueada()) {
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

        // 6-bis) REM_10 no sale de la matriz: se deriva de REM_2 menos el importe a
        // detraer de la Ley 27.430 (ARCA G18). Con detracción cero se informa en cero;
        // con detracción se aplica además su propio tope, que es el piso previsional.
        if ($ctx->importeDetraer !== null) {
            $rem2 = $liq->basesArca['REM_2'] ?? null;
            if ($rem2 === null || $rem2->estaBloqueada()) {
                $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::BaseNoConstruida, 'REM_10 no se puede derivar: REM_2 no fue construida o está bloqueada.', 'bases_arca', 'motor_bases_arca'));
            } else {
                $detraer = Importe::de($ctx->importeDetraer);
                $rem10 = new BaseImponibleResultado('REM_10');
                if ($detraer->esCero()) {
                    $rem10->derivar(Importe::cero(), 'ARCA G18: con detracción cero REM_10 se informa en cero.');
                } else {
                    $rem10->derivar($rem2->importeAplicado->menos($detraer), 'ARCA G18: REM_10 = REM_2 - detracción, con piso mínimo previsional.');
                    $def10 = $periodo->baseArca('REM_10');
                    if ($def10 !== null) {
                        try {
                            $t = $topes->aplicar($def10, $rem10->importeAntesAjustes, ComponenteTope::Mensual);
                            foreach ($t['avisos'] as $a) {
                                $liq->agregarAviso($a);
                            }
                            if (! $t['importe']->igualA($rem10->importeAntesAjustes)) {
                                $rem10->aplicarTope($t['importe'], $t['minimo']?->codigo ?? $t['maximo']?->codigo ?? '?', $t['importe']->igualA($t['minimo']->importe ?? Importe::cero()) ? 'MINIMO' : 'MAXIMO');
                            }
                        } catch (MotorBloqueado $e) {
                            $liq->agregarBloqueo(Bloqueo::desde($e, 'topes'));
                        }
                    }
                }
                $liq->basesArca['REM_10'] = $rem10;
            }
        }

        // 7) aportes personales (MODULO_SISTEMA con sujeto TRABAJADOR): alícuota por condición × base topeada
        $alicuotas = new ResolutorAlicuotas($periodo->alicuotasAportes, $gate);
        $condicion = $ctx->relacion->encuadreArca?->codigoCondicion ?? '1';
        if ($porFormulas) {
            $this->aportesPorAplicacion($liq, $periodo, $ctx, $alicuotas, $condicion);
        } else foreach ($activados as $codigo => [$c, $version]) {
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
            // Cuando REM_10 está calculada, las contribuciones generales y el régimen
            // sustitutivo se liquidan sobre ella y no sobre la base nominal de su
            // alícuota: REM_10 es la base ya neta del importe a detraer (ARCA G18).
            // Los adicionales y las sustituciones de subsistema conservan su propia base.
            $rem10Calculada = isset($liq->basesArca['REM_10']) && $ctx->importeDetraer !== null;
            foreach ($r['lineas'] as $l) {
                $codigoBase = $rem10Calculada && in_array($l['regla'], ['BASE', 'SUSTITUTIVO'], true) ? 'REM_10' : $l['base'];
                $base = $liq->basesArca[$codigoBase] ?? null;
                if ($base === null || $base->estaBloqueada()) {
                    $liq->agregarBloqueo(new Bloqueo($base === null ? CodigoBloqueo::BaseNoConstruida : CodigoBloqueo::BaseBloqueada,
                        "Contribución {$l['subsistema']}: la base {$codigoBase} ".($base === null ? 'no fue construida.' : "está bloqueada [{$base->codigoError}]."), 'contribuciones', 'motor_bases_arca'));

                    continue;
                }
                $l['base'] = $codigoBase;
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
     * Pasa la corrida de fórmulas al resultado del liquidador: los calculados son
     * líneas del recibo, los bloqueados son bloqueos, lo que no aplica queda con su
     * motivo. Devuelve lo que la composición de bases ARCA necesita de cada uno.
     *
     * @return list<ConceptoEnBase>
     */
    private function haberesDesdeFormulas(Liquidacion $liq, ContextoPeriodo $periodo, EstadoCorrida $corrida): array
    {
        $enBase = [];
        foreach ($corrida->conceptos as $codigo => $r) {
            $codigo = (string) $codigo;
            if ($r->estado === ResultadoConcepto::NO_APLICA) {
                $liq->noEjecutados[] = ['codigo' => $codigo, 'motivo' => (string) $r->motivo];

                continue;
            }
            if ($r->estado !== ResultadoConcepto::CALCULADO) {
                // BLOQUEADO o NO_MODELADO: el concepto corresponde y no se puede afirmar.
                // Igual que en las bases de Zonda, bloquea toda base ARCA que integre.
                $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::tryFrom((string) $r->codigoError) ?? CodigoBloqueo::ConfiguracionInvalida,
                    "{$codigo} [{$r->codigoError}]: {$r->motivo}", 'haberes', 'motor_concepto_versiones', null, false, $codigo));
                $enBase[] = ConceptoEnBase::bloqueado($codigo, $r->codigoError);

                continue;
            }

            $concepto = isset($periodo->versiones[$codigo]) ? $periodo->versiones[$codigo]->concepto() : null;
            if ($concepto === null) {
                $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::ConceptoSinVersion,
                    "{$codigo}: la fórmula lo calculó pero el concepto no está en el catálogo vigente del período.", 'haberes', 'motor_conceptos', null, false, $codigo));
                $enBase[] = ConceptoEnBase::bloqueado($codigo, CodigoBloqueo::ConceptoSinVersion->value);

                continue;
            }

            // El importe del DSL es exacto: la fórmula ya aplicó su propio redondeo y
            // el motor no agrega redondeos implícitos (P-7).
            $importe = Importe::de((string) $r->importeResultado);
            $linea = new LineaHaber($concepto, $importe, $importe, $r->cantidad,
                $r->valorUnitario === null ? null : Importe::de((string) $r->valorUnitario), null, null,
                ['origen' => 'FORMULA', 'variante' => $r->variante, 'referencias' => $r->traza?->resumen() ?? []]);
            if ($concepto->tipo === TipoConcepto::Descuento) {
                $liq->descuentos[] = $linea;
            } else {
                $liq->haberes[] = $linea;
            }
            $liq->traza->registrar('haberes', "{$codigo} = {$importe->aTexto(2)}", ['variante' => $r->variante], $codigo);
            $enBase[] = ConceptoEnBase::calculado($codigo, $importe);
        }
        $liq->etapas['aplicabilidad'] = 'OK';
        $liq->etapas['haberes'] = $liq->haberes === [] && $liq->tieneBloqueos() ? 'BLOQUEADA' : 'OK';

        foreach ($corrida->bases as $codigoBase => $b) {
            $res = new BaseImponibleResultado((string) $codigoBase, 'ZONDA');
            foreach ($b->incluidos as $c => $imp) {
                $res->incluir((string) $c, Importe::de((string) $imp));
            }
            foreach ($b->excluidos as $c => $motivo) {
                $res->excluir((string) $c, (string) $motivo);
            }
            if (! $b->esOk()) {
                $res->bloquear('BASE_ZONDA_BLOQUEADA', (string) $b->motivo);
            }
            $liq->basesZonda[(string) $codigoBase] = $res;
        }

        return $enBase;
    }

    /**
     * Aportes personales desde las aplicaciones APORTE de Seguridad Social: base
     * REM_n de la aplicación × alícuota por condición ARCA. Obra social y FSR son de
     * su propio módulo. Una aplicación REGIMEN sin régimen que la habilite no aplica
     * (no se busca alícuota ni se bloquea); habilitada y sin alícuota, bloquea.
     */
    private function aportesPorAplicacion(Liquidacion $liq, ContextoPeriodo $periodo, ContextoRelacionLaboral $ctx, ResolutorAlicuotas $alicuotas, string $condicion): void
    {
        $modulo = [];
        foreach ($periodo->subsistemas as $s) {
            $modulo[$s->codigo] = $s->moduloResponsable;
        }
        $porRegimen = $this->subsistemasPorRegimen($periodo, $ctx);

        foreach ($periodo->aplicacionesPorClave() as $clave => $ap) {
            if ($ap->tipoAplicacion->value !== 'APORTE' || ! $ap->activo || $ap->codigoBase === null) {
                continue;
            }
            if (($modulo[$ap->codigoSubsistema] ?? null) !== 'SEGURIDAD_SOCIAL') {
                $liq->noEjecutados[] = ['codigo' => $clave, 'motivo' => "aporte {$ap->codigoSubsistema}: lo liquida el módulo ".($modulo[$ap->codigoSubsistema] ?? '?')];

                continue;
            }
            if ($ap->esPorRegimen() && ! in_array($ap->codigoSubsistema, $porRegimen, true)) {
                continue; // ya informado como aplicación omitida al componer las bases
            }
            $base = $liq->basesArca[$ap->codigoBase] ?? null;
            if ($base === null || $base->estaBloqueada()) {
                $liq->agregarBloqueo(new Bloqueo($base === null ? CodigoBloqueo::BaseNoConstruida : CodigoBloqueo::BaseBloqueada,
                    "Aporte {$ap->codigoSubsistema}: la base {$ap->codigoBase} ".($base === null ? 'no fue construida.' : "está bloqueada [{$base->codigoError}]."), 'aportes', 'motor_bases_arca'));

                continue;
            }
            try {
                $r = $alicuotas->aporte($ap->codigoSubsistema, $condicion);
                if ($r['aviso'] !== null) {
                    $liq->agregarAviso($r['aviso']);
                }
                $a = $r['alicuota'];
                $importe = $a->porcentaje->aplicarA($a->aplicaTope ? $base->importeAplicado : $base->importeAntesAjustes)->redondeado(2);
                $liq->aportes[] = new LineaAporte($this->conceptoAportePara($periodo, $ap->codigoSubsistema, $ap->codigoBase), $ap->codigoSubsistema,
                    $ap->codigoBase, $base->importeAplicado, $a->porcentaje, $importe, $a->codigo, $a->codigoCondicion);
            } catch (MotorBloqueado $e) {
                $liq->agregarBloqueo(Bloqueo::desde($e, 'aportes'));
            }
        }
    }

    /** El concepto de recibo del aporte (APJUB, APINSSJYP…), si el catálogo lo tiene. */
    private function conceptoAportePara(ContextoPeriodo $periodo, string $subsistema, string $codigoBase): string
    {
        foreach ($periodo->versiones as $codigo => $version) {
            $c = $version->concepto();
            if ($c->esModuloSistema() && $c->tipo === TipoConcepto::Descuento && $c->baseModulo === $codigoBase
                && $this->subsistemaDeConcepto($periodo, $c) === $subsistema) {
                return (string) $codigo;
            }
        }

        return 'AP_'.$subsistema;
    }

    /**
     * Subsistemas que los regímenes vigentes de la relación habilitan (su destino y,
     * en una sustitución, el subsistema que entra). Es lo que activa una aplicación
     * REGIMEN como RENATRE.
     *
     * @return list<string>
     */
    private function subsistemasPorRegimen(ContextoPeriodo $periodo, ContextoRelacionLaboral $ctx): array
    {
        $codigos = array_map(fn ($r) => $r->codigoRegimen, $ctx->relacion->regimenesVigentesA($periodo->fechaResolucion));
        $subsistemas = [];
        foreach ($periodo->regimenes as $r) {
            if (in_array($r->codigo, $codigos, true) && $r->destino !== null) {
                foreach (explode('+', $r->destino) as $d) {
                    $subsistemas[] = trim($d);
                }
            }
        }

        return array_values(array_unique($subsistemas));
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
