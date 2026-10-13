<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-seed de la normativa desde el Excel funcional v5 (modelo DBML v2, auditoría
 * ARCA del 2026-09-25, gate Etapa 3A = LISTO_DESARROLLO). Reemplaza los datos que
 * venían del Excel del 30/08.
 *
 * Qué trae de nuevo, además de más filas:
 *   · 507 incidencias ARCA con 0 PENDIENTE (antes 481 con 216 sin decidir),
 *     alícuotas y topes CONFIRMADO: el gate de PRODUCCION deja de bloquear por dato faltante.
 *   · la quinta base BASE_ACUERDO_5_6_2 y la matriz completa 5 x 37.
 *   · topes con proporcional_dias, mínimo sobre REM_10 y máximo también para REM_4.
 *   · la distribución del RIFL por subsistema y las reglas de base diferencial de OS.
 *   · las 26 versiones de fórmula en DSL, que carga MotorFormulasV5Seeder: necesitan
 *     el snapshot del concepto, que publica MotorVersionesInicialesSeeder.
 *
 * NO se siembra lo que depende de identificadores del libro funcional —
 * VALORES_VARIABLE (DIVJOR por convenio), VALORES_PARAMETROS, PERFIL,
 * PERFIL_CONCEPTO, CATEGORIA_CONCEPTO y CONCEPTO_LEGACY_MAPEO — porque sus
 * id_convenio / id_categoria / empresa_id son del libro (y el propio Excel aclara
 * que los empresa_id 1/2 son contextos anonimizados), no de sue007s / sue006s /
 * sue086s. Sembrarlos apuntaría a registros equivocados. Se cargan por pantalla
 * cuando se mapeen los convenios reales; los perfiles del v5, además, son el caso
 * testigo DEMO y no la regla productiva.
 */
return new class extends MigracionMotor
{
    private const DIR = __DIR__.'/../../seeders/motor/datos/v5';

    /** @var array<string, int> */
    private array $aplicaciones = [];

    /** @var array<string, int> */
    private array $basesZonda = [];

    private string $ahora;

    public function up(): void
    {
        $this->ahora = now()->toDateTimeString();

        // COMIDA y VIAT_ESP llegan con la unidad en blanco: el origen las marca
        // PENDIENTE_UNIDAD (trazabilidad B-2, filas 31-32). No se les asigna una
        // unidad por inferencia — queda NULL y el motor bloquea si la necesita.
        Schema::table('motor_tipos_novedad', function (Blueprint $t) {
            $t->string('unidad', 20)->nullable()->change();
        });

        // tipo_regla gana SUSTITUCION_SUBSISTEMA (22 caracteres): RENATRE no sustituye
        // la alícuota sino el subsistema completo, reemplazando FNE.
        Schema::table('motor_regimenes_contributivos', function (Blueprint $t) {
            $t->string('tipo_regla', 30)->change();
        });

        // La unicidad de versiones tenía dos índices que ignoran la variante.
        // BASICO publica JORNADA_PACTADA y LARGA_DISTANCIA con la misma fecha de
        // inicio y el mismo número de versión, así que ambos deben incluirla.
        if ($this->esMysql()) {
            $indices = collect(DB::select('SHOW INDEX FROM `motor_concepto_versiones`'))->pluck('Key_name')->unique();
            if ($indices->contains('motor_versiones_concepto_vig_unique')) {
                DB::statement('ALTER TABLE `motor_concepto_versiones` DROP INDEX `motor_versiones_concepto_vig_unique`');
            }
            if ($indices->contains('motor_versiones_concepto_num_unique')) {
                DB::statement('ALTER TABLE `motor_concepto_versiones` DROP INDEX `motor_versiones_concepto_num_unique`');
                DB::statement('ALTER TABLE `motor_concepto_versiones` ADD UNIQUE `motor_cv_concepto_variante_num_uq` (`id_concepto`, `variante`, `numero_version`)');
            }
        }

        DB::transaction(function () {
            $this->catalogosArca();
            $this->variablesYModulos();
            // Parámetros y tipos de novedad antes que los conceptos: motor_conceptos
            // tiene FK a ambos por código natural (el concepto ADTIT referencia el
            // parámetro ADTIT, que el v2 renombró desde PORC_TITULO).
            $this->parametros();
            $this->tiposNovedad();
            $this->conceptos();
            $this->basesDeZonda();
            $this->incidenciasArca();
            $this->normativaContributiva();
            $this->escalas();
            $this->retirarLoQueElV5NoDeclara();
        });

        $this->verificarConteos();
    }

    /** @return list<array<string, mixed>> */
    private function datos(string $hoja): array
    {
        $ruta = self::DIR."/{$hoja}.php";
        if (! is_file($ruta)) {
            throw new \RuntimeException("Falta el archivo de datos {$ruta}. Regenerar con generar_datos_v5.py.");
        }

        return require $ruta;
    }

    private function sello(array $fila): array
    {
        return $fila + ['created_at' => $this->ahora, 'updated_at' => $this->ahora];
    }

    private function catalogosArca(): void
    {
        foreach ($this->datos('bases_arca') as $f) {
            DB::table('motor_bases_arca')->updateOrInsert(
                ['codigo_base' => $f['codigo_base']],
                $this->sello([
                    'descripcion' => $f['descripcion'] ?? $f['codigo_base'],
                    'admite_tope' => in_array($f['admite_tope'] ?? 'N', ['S', '1', 1], true),
                ])
            );
        }

        foreach ($this->datos('subsistemas') as $f) {
            DB::table('motor_subsistemas')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello(['descripcion' => $f['descripcion'] ?? $f['codigo']])
            );
        }

        // El id_subsistema_aplicacion del libro (1..13) se traduce por (subsistema, tipo).
        foreach ($this->datos('subsistema_aplicacion') as $f) {
            $idSub = (int) DB::table('motor_subsistemas')
                ->where('id_subsistema', (int) $f['id_subsistema'])->value('id_subsistema');
            DB::table('motor_subsistema_aplicacion')->updateOrInsert(
                ['id_subsistema' => $idSub, 'tipo_aplicacion' => $f['tipo_aplicacion']],
                $this->sello([
                    'codigo_base' => $f['codigo_base'],
                    'columna_lsd' => $f['columna_lsd'],
                    'activo' => (bool) ($f['activo'] ?? 1),
                    'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR',
                ])
            );
            $this->aplicaciones[(string) $f['id_subsistema_aplicacion']] = (int) DB::table('motor_subsistema_aplicacion')
                ->where('id_subsistema', $idSub)->where('tipo_aplicacion', $f['tipo_aplicacion'])
                ->value('id_subsistema_aplicacion');
        }

        foreach ($this->datos('encuadramientos_patronales') as $f) {
            DB::table('motor_encuadramientos_patronales')->updateOrInsert(
                ['encuadramiento' => $f['encuadramiento']],
                $this->sello(['descripcion' => $f['descripcion'], 'fuente_normativa' => $f['fuente_normativa'] ?? null])
            );
        }

        foreach ($this->datos('componentes_tope') as $f) {
            DB::table('motor_componentes_tope')->updateOrInsert(
                ['codigo_componente' => $f['codigo_componente'] ?? $f['componente'] ?? $f['codigo'] ?? null],
                $this->sello(['descripcion' => $f['descripcion'] ?? ''])
            );
        }
    }

    private function variablesYModulos(): void
    {
        foreach ($this->datos('variables') as $f) {
            DB::table('motor_variables')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'unidad' => $f['unidad'],
                    'origen' => $f['origen'],
                    'activo' => (bool) $f['activo'],
                    'observaciones' => $f['observaciones'] ?? null,
                ])
            );
        }

        foreach ($this->datos('modulos') as $f) {
            DB::table('motor_modulos')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'unidad_resultado' => $f['unidad_resultado'],
                    'version' => $f['version'] ?? 'v1',
                    'requiere_historico' => (bool) ($f['requiere_historico'] ?? 0),
                    'meses_historico' => $f['meses_historico'] !== null ? (int) $f['meses_historico'] : null,
                    'activo' => (bool) ($f['activo'] ?? 1),
                    'observaciones' => $f['observaciones'] ?? null,
                ])
            );
        }

        foreach ($this->datos('modulo_dependencia') as $f) {
            DB::table('motor_modulo_dependencia')->updateOrInsert(
                ['codigo_modulo' => $f['codigo_modulo'], 'tipo' => $f['tipo'], 'codigo_referencia' => $f['codigo_referencia']],
                $this->sello([])
            );
        }
    }

    private function conceptos(): void
    {
        foreach ($this->datos('tab_conceptos') as $f) {
            DB::table('motor_conceptos')->updateOrInsert(
                ['codigo_concepto' => $f['codigo_concepto']],
                $this->sello([
                    'nombre_concepto' => $f['nombre_concepto'],
                    'alias_recibo' => $f['alias_recibo'],
                    'convenio_documental' => $f['convenio_documental'],
                    'tipo_concepto' => $f['tipo_concepto'],
                    'naturaleza' => $f['naturaleza'],
                    'clasificacion_funcional' => $f['clasificacion_funcional'],
                    'subtipo_funcional' => $f['subtipo_funcional'],
                    'tipo_variacion' => $f['tipo_variacion'],
                    'signo' => $f['signo'],
                    'unidad_principal' => $f['unidad_principal'],
                    'modo_calculo' => $f['modo_calculo'],
                    'modulo_responsable' => $f['modulo_responsable'],
                    'base_modulo' => $f['base_modulo'],
                    'codigo_parametro' => $f['codigo_parametro'],
                    'editable_manual' => (bool) $f['editable_manual'],
                    'repetible' => (bool) $f['repetible'],
                    'permite_importe_cero' => (bool) $f['permite_importe_cero'],
                    'imprime_recibo' => (bool) $f['imprime_recibo'],
                    'es_concepto_sistema' => (bool) $f['es_concepto_sistema'],
                    'tipo_repeticion' => $f['tipo_repeticion'],
                    'orden_visual_sugerido' => $f['orden_visual_sugerido'] !== null ? (int) $f['orden_visual_sugerido'] : null,
                    'vigencia_desde' => $f['vigencia_desde'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'activo' => (bool) $f['activo'],
                    'observaciones_funcionales' => $f['observaciones_funcionales'],
                ])
            );
        }
    }

    private function parametros(): void
    {
        $vigentes = [];
        foreach ($this->datos('parametros') as $f) {
            $vigentes[] = $f['codigo'];
            DB::table('motor_parametros')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'tipo_parametro' => $f['tipo_parametro'],
                    'unidad' => $f['unidad'],
                    'activo' => (bool) $f['activo'],
                ])
            );
        }

        // Parámetros que el v2 retiró: PORC_TITULO se unificó en ADTIT (P-9);
        // HS_TOLERANCIA_HORARIA y PORC_PLUS_PLURALIDAD pasaron a VALCAT (F-1); los
        // RECARGO_* eran propuestas y hoy son literales justificados en la fórmula.
        // Baja lógica, no borrado: pueden estar referenciados por datos históricos.
        $retirados = ['PORC_TITULO', 'HS_TOLERANCIA_HORARIA', 'PORC_PLUS_PLURALIDAD',
            'RECARGO_HE_50', 'RECARGO_HE_100', 'RECARGO_KM_SAB_DOM'];
        DB::table('motor_parametros')->whereIn('codigo', $retirados)
            ->update(['activo' => false, 'updated_at' => $this->ahora]);

        // El concepto ADTIT apuntaba al parámetro viejo.
        DB::table('motor_conceptos')->where('codigo_parametro', 'PORC_TITULO')
            ->update(['codigo_parametro' => 'ADTIT', 'updated_at' => $this->ahora]);

        // REDONDEO_DECIMALES, DIVISOR_DIAS y HORAS_MES_REFERENCIA siguen activos:
        // son los parámetros técnicos que usa el orquestador actual. En el modelo v2
        // su equivalente funcional son VAR(DIVJOR) y VAR(HMJORN), de alcance convenio;
        // la sustitución es parte del port del DSL, no de este re-seed.
    }

    private function tiposNovedad(): void
    {
        foreach ($this->datos('tipos_novedad') as $f) {
            DB::table('motor_tipos_novedad')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'unidad' => $f['unidad'],
                    'activo' => (bool) $f['activo'],
                ])
            );
        }
    }

    private function basesDeZonda(): void
    {
        foreach ($this->datos('bases_zonda') as $f) {
            DB::table('motor_bases_zonda')->updateOrInsert(
                ['codigo_base' => $f['codigo_base']],
                $this->sello([
                    'nombre' => $f['nombre'],
                    'descripcion' => $f['descripcion'],
                    'activo' => (bool) $f['activo'],
                    'estado_validacion' => $f['estado_validacion'],
                ])
            );
            $this->basesZonda[(string) $f['id_base_zonda']] = (int) DB::table('motor_bases_zonda')
                ->where('codigo_base', $f['codigo_base'])->value('id_base_zonda');
        }

        foreach ($this->datos('base_zonda_incidencia') as $f) {
            $idBase = $this->basesZonda[(string) $f['id_base_zonda']] ?? null;
            if ($idBase === null) {
                throw new \RuntimeException("Base de Zonda {$f['id_base_zonda']} sin correspondencia.");
            }
            DB::table('motor_base_zonda_incidencia')->updateOrInsert(
                ['id_base_zonda' => $idBase, 'codigo_concepto' => $f['codigo_concepto'], 'vigencia_desde' => $f['vigencia_desde']],
                $this->sello([
                    'incidencia' => $f['incidencia'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'observaciones' => $f['observaciones'],
                ])
            );
        }
    }

    private function incidenciasArca(): void
    {
        foreach ($this->datos('concepto_incidencia_arca') as $f) {
            $idAplic = $this->aplicaciones[(string) $f['id_subsistema_aplicacion']] ?? null;
            if ($idAplic === null) {
                throw new \RuntimeException("Aplicación de subsistema {$f['id_subsistema_aplicacion']} sin correspondencia.");
            }
            DB::table('motor_concepto_incidencia_arca')->updateOrInsert(
                ['codigo_concepto' => $f['codigo_concepto'], 'id_subsistema_aplicacion' => $idAplic, 'vigencia_desde' => $f['vigencia_desde']],
                $this->sello([
                    'incidencia' => $f['incidencia'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                    'observaciones' => $f['observaciones'],
                ])
            );
        }
    }

    private function normativaContributiva(): void
    {
        foreach ($this->datos('alicuotas_aportes') as $f) {
            DB::table('motor_alicuotas_aportes')->updateOrInsert(
                ['codigo' => $f['id_alicuota_aporte']],
                $this->sello([
                    'id_subsistema' => (int) $f['id_subsistema'],
                    // NULL = comodín TODAS (desviación §11: no se siembra un pseudo-código)
                    'codigo_condicion' => $f['condicion_arca'] === 'TODAS' ? null : $f['condicion_arca'],
                    'porcentaje' => $f['porcentaje'],
                    'base_aplicable' => $f['base_aplicable'],
                    'aplica_tope' => in_array($f['aplica_tope'], ['S', '1', 1], true),
                    'vigencia_desde' => $f['vigencia_desde'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                ])
            );
        }

        foreach ($this->datos('alicuotas_contribuciones') as $f) {
            DB::table('motor_alicuotas_contribuciones')->updateOrInsert(
                ['codigo' => $f['id_alicuota']],
                $this->sello([
                    'encuadramiento' => $f['encuadramiento'],
                    'id_subsistema' => (int) $f['id_subsistema'],
                    'porcentaje' => $f['porcentaje'],
                    'base_aplicable' => $f['base_aplicable'],
                    'vigencia_desde' => $f['vigencia_desde'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                ])
            );
        }

        foreach ($this->datos('topes_minimos') as $f) {
            DB::table('motor_topes_minimos')->updateOrInsert(
                ['codigo' => $f['id_tope']],
                $this->sello([
                    'periodo' => substr((string) $f['periodo'], 0, 7),
                    'codigo_base' => $f['codigo_base'],
                    'tipo_tope' => $f['tipo_tope'],
                    'componente' => $f['componente'],
                    'importe' => $f['importe'],
                    'proporcional_dias' => in_array($f['proporcional_dias'], ['S', '1', 1], true),
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                ])
            );
        }

        foreach ($this->datos('regimenes_contributivos') as $f) {
            DB::table('motor_regimenes_contributivos')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'tipo_regla' => $f['tipo_regla'],
                    'sujeto_obligado' => $f['sujeto_obligado'],
                    'porcentaje' => $f['porcentaje'],
                    'destino' => $f['destino'],
                    'reemplaza_subsistema' => $f['reemplaza_subsistema'],
                    'base_aplicable' => $f['base_aplicable'],
                    'duracion_meses' => $f['duracion_meses'] !== null ? (int) $f['duracion_meses'] : null,
                    'orden_aplicacion' => (int) $f['orden_aplicacion'],
                    'vigencia_desde' => $f['vigencia_desde'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                ])
            );
        }

        // El id_regimen del libro es el código (RC-00X); el de la base es sustituto.
        $porCodigoLibro = [];
        foreach ($this->datos('regimenes_contributivos') as $f) {
            $porCodigoLibro[$f['id_regimen']] = (int) DB::table('motor_regimenes_contributivos')
                ->where('codigo', $f['codigo'])->value('id_regimen');
        }

        foreach ($this->datos('regimen_distribucion') as $f) {
            $idRegimen = $porCodigoLibro[$f['id_regimen']] ?? null;
            if ($idRegimen === null) {
                throw new \RuntimeException("Régimen {$f['id_regimen']} sin correspondencia para su distribución.");
            }
            DB::table('motor_regimen_distribucion')->updateOrInsert(
                ['id_regimen' => $idRegimen, 'id_subsistema' => (int) $f['id_subsistema'], 'vigencia_desde' => $f['vigencia_desde']],
                $this->sello([
                    'destino' => $f['destino'],
                    'porcentaje' => $f['porcentaje'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                ])
            );
        }

        foreach ($this->datos('reglas_base_diferencial_arca') as $f) {
            DB::table('motor_reglas_base_diferencial_arca')->updateOrInsert(
                ['codigo' => $f['codigo'], 'codigo_base' => $f['codigo_base']],
                $this->sello([
                    'tipo_diferencial' => $f['tipo_diferencial'],
                    'disparador' => $f['disparador'],
                    'metodo' => $f['metodo'],
                    'modulo_responsable' => $f['modulo_responsable'],
                    'vigencia_desde' => $f['vigencia_desde'],
                    'vigencia_hasta' => $f['vigencia_hasta'],
                    'estado_validacion' => $f['estado_validacion'],
                    'fuente_normativa' => $f['fuente_normativa'],
                    'observaciones' => $f['observaciones'],
                ])
            );
        }

        foreach ([['condiciones_arca', 'motor_condiciones_arca', 'codigo_condicion'],
            ['modalidades_arca', 'motor_modalidades_arca', 'codigo_modalidad'],
            ['situaciones_revista_arca', 'motor_situaciones_revista_arca', 'codigo_situacion']] as [$hoja, $tabla, $clave]) {
            foreach ($this->datos($hoja) as $f) {
                $codigo = $f[$clave] ?? $f['codigo'] ?? null;
                if ($codigo === null) {
                    continue;
                }
                DB::table($tabla)->updateOrInsert(
                    [$clave => $codigo],
                    $this->sello(['descripcion' => $f['descripcion'] ?? ''])
                );
            }
        }
    }

    private function escalas(): void
    {
        $modelos = [];
        foreach ($this->datos('modelos_antiguedad') as $f) {
            DB::table('motor_modelos_antiguedad')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $this->sello([
                    'descripcion' => $f['descripcion'],
                    'metodo_calculo' => $f['metodo_calculo'],
                    'porcentaje_por_anio' => $f['porcentaje_por_anio'] ?? null,
                    'porcentaje_maximo' => $f['porcentaje_maximo'] ?? null,
                    'version' => (int) ($f['version'] ?? 1),
                    'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR',
                    'activo' => (bool) ($f['activo'] ?? 1),
                    'observaciones' => $f['observaciones'] ?? null,
                ])
            );
            $modelos[(string) ($f['id_modelo_antiguedad'] ?? $f['codigo'])] = (int) DB::table('motor_modelos_antiguedad')
                ->where('codigo', $f['codigo'])->value('id_modelo_antiguedad');
        }

        foreach ($this->datos('antiguedad_tramos') as $f) {
            $idModelo = $modelos[(string) $f['id_modelo_antiguedad']] ?? null;
            if ($idModelo === null) {
                continue;
            }
            DB::table('motor_antiguedad_tramos')->updateOrInsert(
                ['id_modelo_antiguedad' => $idModelo, 'anios_desde' => $f['anios_desde']],
                $this->sello([
                    'anios_hasta' => $f['anios_hasta'],
                    'porcentaje' => $f['porcentaje'],
                    'orden' => (int) ($f['orden'] ?? 0),
                ])
            );
        }
    }

    /**
     * El v5 no sólo agrega: también retira filas, y la ausencia es significativa.
     *
     *  · ACTAFUT queda sin sus 13 incidencias a propósito ("combinaciones ausentes
     *    deliberadamente; exige acuerdo/vigencia y bloquea si se usa sin matriz"):
     *    si se conservaran, el concepto dejaría de bloquear.
     *  · los mínimos sobre REM_1/REM_4/REM_5 se reemplazaron por el mínimo sobre
     *    REM_10 (piso previsional); conservar un mínimo donde ARCA ya no lo aplica
     *    produciría un cálculo incorrecto, no un aviso.
     *
     * Es un borrado físico sobre tablas normativas, contra el criterio general de
     * RT-010, porque ninguna de las dos tiene marca de baja ni vigencia utilizable
     * (las de ACTAFUT nacen con la misma fecha de inicio que la matriz nueva, así
     * que no se pueden cerrar) y porque un dato normativo fantasma es peor que su
     * ausencia. Ninguna fue usada en una liquidación — no hay liquidaciones — y el
     * dato anterior sigue versionado en database/seeders/motor/datos/.
     */
    private function retirarLoQueElV5NoDeclara(): void
    {
        $topes = array_column($this->datos('topes_minimos'), 'id_tope');
        $retirados = DB::table('motor_topes_minimos')->whereNotIn('codigo', $topes)->delete();

        $vigentes = [];
        foreach ($this->datos('concepto_incidencia_arca') as $f) {
            $idAplic = $this->aplicaciones[(string) $f['id_subsistema_aplicacion']] ?? null;
            $vigentes[] = $f['codigo_concepto'].'|'.$idAplic.'|'.$f['vigencia_desde'];
        }
        $sobrantes = [];
        foreach (DB::table('motor_concepto_incidencia_arca')->get(['id_incidencia_arca', 'codigo_concepto', 'id_subsistema_aplicacion', 'vigencia_desde']) as $fila) {
            $clave = $fila->codigo_concepto.'|'.$fila->id_subsistema_aplicacion.'|'.substr((string) $fila->vigencia_desde, 0, 10);
            if (! in_array($clave, $vigentes, true)) {
                $sobrantes[] = $fila->id_incidencia_arca;
            }
        }
        if ($sobrantes !== []) {
            DB::table('motor_concepto_incidencia_arca')->whereIn('id_incidencia_arca', $sobrantes)->delete();
        }
    }

    private function verificarConteos(): void
    {
        $esperado = [
            'motor_conceptos' => 40,
            'motor_bases_zonda' => 5,
            'motor_base_zonda_incidencia' => 185,
            'motor_concepto_incidencia_arca' => 507,
            'motor_topes_minimos' => 9,
            'motor_alicuotas_aportes' => 4,
            'motor_alicuotas_contribuciones' => 8,
            'motor_regimenes_contributivos' => 9,
            'motor_regimen_distribucion' => 4,
            'motor_reglas_base_diferencial_arca' => 2,
            'motor_variables' => 8,
            'motor_modulos' => 3,
            'motor_modulo_dependencia' => 2,
        ];
        $diferencias = [];
        foreach ($esperado as $tabla => $n) {
            $real = (int) DB::table($tabla)->count();
            if ($real !== $n) {
                $diferencias[] = "{$tabla}: esperado {$n}, hay {$real}";
            }
        }
        // Las incidencias no pueden quedar sin decidir: la auditoría del 2026-09-25
        // cerró las 507 en S o N.
        $pendientes = (int) DB::table('motor_concepto_incidencia_arca')->where('incidencia', 'PENDIENTE')->count();
        if ($pendientes > 0) {
            $diferencias[] = "quedaron {$pendientes} incidencias ARCA en PENDIENTE";
        }
        if ($diferencias !== []) {
            throw new \RuntimeException('El re-seed del v5 no cuadra: '.implode(' · ', $diferencias));
        }
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
