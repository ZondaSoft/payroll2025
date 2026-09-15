<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Motor de Cálculo — datos normativos y de configuración iniciales, tomados del
 * Excel funcional del especialista (Zonda_Motor_Calculo_2026-08-30.xlsx) vía los
 * arrays de database/seeders/motor/datos/*.php.
 *
 * IDEMPOTENTE Y NO DESTRUCTIVA: insertOrIgnore sobre la clave natural única de
 * cada tabla; si la fila ya existe no se toca. down() no borra nada.
 *
 * Todo nace SIN_CONFIRMAR (o SIN_DATO donde el Excel lo dice): por regla del
 * motor, en PRODUCCION nada de esto habilita el cálculo hasta que se confirme
 * contra ARCA. Excepción documentada: los tres parámetros TÉCNICOS
 * (REDONDEO_DECIMALES, DIVISOR_DIAS, HORAS_MES_REFERENCIA) nacen CONFIRMADO
 * porque no son normativa sino configuración del propio motor.
 *
 * ⚠ INSERT IGNORE en MySQL también silencia violaciones de FK: por eso cada
 * bloque verifica el conteo y aborta si falta alguna fila.
 */
return new class extends Migration
{
    private string $ahora;

    public function up(): void
    {
        $this->ahora = now()->toDateTimeString();

        $this->bloque('motor_bases_arca', 'bases_arca', fn ($f) => [
            'id_base' => $f['id_base'], 'codigo_base' => $f['codigo_base'], 'descripcion' => $f['descripcion'],
            'sujeto_obligado' => $f['sujeto_obligado'], 'admite_tope' => (int) $f['admite_tope'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_subsistemas', 'subsistemas', fn ($f) => [
            'id_subsistema' => $f['id_subsistema'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'],
            'modulo_responsable' => $f['modulo_responsable'], 'base_aportes' => $f['base_aportes'],
            'base_contribuciones' => $f['base_contribuciones'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_encuadramientos_patronales', 'encuadramientos_patronales', fn ($f) => [
            'encuadramiento' => $f['encuadramiento'], 'descripcion' => $f['descripcion'],
            'fuente_normativa' => $f['fuente_normativa'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_componentes_tope', 'componentes_tope', fn ($f) => [
            'codigo_componente' => $f['codigo_componente'], 'descripcion' => $f['descripcion'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_condiciones_arca', 'condiciones_arca', fn ($f) => [
            'codigo_condicion' => (string) $f['codigo_condicion'], 'descripcion' => $f['descripcion'],
            'consecuencia_general' => $f['consecuencia_general'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_modalidades_arca', 'modalidades_arca', fn ($f) => [
            'codigo_modalidad' => (string) $f['codigo_modalidad'], 'descripcion' => $f['descripcion'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_situaciones_revista_arca', 'situaciones_revista_arca', fn ($f) => [
            'codigo_situacion' => (string) $f['codigo_situacion'], 'descripcion' => $f['descripcion'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_subsistema_aplicacion', 'subsistema_aplicacion', fn ($f) => [
            'id_subsistema_aplicacion' => $f['id_subsistema_aplicacion'], 'id_subsistema' => $f['id_subsistema'],
            'tipo_aplicacion' => $f['tipo_aplicacion'], 'codigo_base' => $f['codigo_base'], 'columna_lsd' => $f['columna_lsd'],
            'activo' => (int) ($f['activo'] ?? 1), 'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR',
            'observaciones' => $f['observaciones'],
        ]);
        $this->bloque('motor_alicuotas_contribuciones', 'alicuotas_contribuciones', fn ($f) => [
            'codigo' => $f['id_alicuota'], 'encuadramiento' => $f['encuadramiento'], 'id_subsistema' => $f['id_subsistema'],
            'porcentaje' => $f['porcentaje'], 'base_aplicable' => $f['base_aplicable'],
            'vigencia_desde' => $f['vigencia_desde'], 'vigencia_hasta' => $f['vigencia_hasta'],
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'fuente_normativa' => $f['fuente_normativa'],
        ]);
        $this->bloque('motor_alicuotas_aportes', 'alicuotas_aportes', fn ($f) => [
            'codigo' => $f['id_alicuota_aporte'], 'id_subsistema' => $f['id_subsistema'],
            'codigo_condicion' => ($f['condicion_arca'] === null || strtoupper((string) $f['condicion_arca']) === 'TODAS') ? null : (string) $f['condicion_arca'],
            'porcentaje' => $f['porcentaje'], 'base_aplicable' => $f['base_aplicable'], 'aplica_tope' => (int) $f['aplica_tope'],
            'vigencia_desde' => $f['vigencia_desde'], 'vigencia_hasta' => $f['vigencia_hasta'],
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'fuente_normativa' => $f['fuente_normativa'],
        ]);
        $this->bloque('motor_topes_minimos', 'topes_minimos', fn ($f) => [
            'codigo' => $f['id_tope'], 'periodo' => $f['periodo'], 'codigo_base' => $f['codigo_base'], 'tipo_tope' => $f['tipo_tope'],
            'componente' => $f['componente'], 'importe' => $f['importe'],
            'estado_validacion' => $f['estado_validacion'] ?? ($f['importe'] === null ? 'SIN_DATO' : 'SIN_CONFIRMAR'),
            'fuente_normativa' => $f['fuente_normativa'],
        ]);
        $this->bloque('motor_regimenes_contributivos', 'regimenes_contributivos', fn ($f) => [
            'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'], 'tipo_regla' => $f['tipo_regla'],
            'sujeto_obligado' => $f['sujeto_obligado'], 'porcentaje' => $f['porcentaje'], 'destino' => $f['destino'],
            'base_aplicable' => $f['base_aplicable'], 'duracion_meses' => $f['duracion_meses'], 'orden_aplicacion' => $f['orden_aplicacion'],
            'vigencia_desde' => $f['vigencia_desde'], 'vigencia_hasta' => $f['vigencia_hasta'],
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'fuente_normativa' => $f['fuente_normativa'],
        ]);

        // --- Parámetros: 15 del Excel + 3 técnicos + 3 PENDIENTE ALTA (los referencian conceptos) ---
        $this->bloque('motor_parametros', 'parametros', fn ($f) => [
            'id_parametro' => $f['id_parametro'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'],
            'tipo_parametro' => $f['tipo_parametro'], 'unidad' => $f['unidad'], 'es_tecnico' => 0, 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $extra = [
            ['codigo' => 'REDONDEO_DECIMALES', 'descripcion' => 'Decimales de redondeo por defecto (regla PARAMETRIZADA)', 'tipo_parametro' => 'ENTERO', 'unidad' => 'DECIMALES', 'es_tecnico' => 1, 'activo' => 1],
            ['codigo' => 'DIVISOR_DIAS', 'descripcion' => 'Divisor de días para proporcionar el básico mensual', 'tipo_parametro' => 'ENTERO', 'unidad' => 'DIAS', 'es_tecnico' => 1, 'activo' => 1],
            ['codigo' => 'HORAS_MES_REFERENCIA', 'descripcion' => 'Horas mensuales de referencia cuando la jornada no está informada', 'tipo_parametro' => 'HORAS', 'unidad' => 'HORAS', 'es_tecnico' => 1, 'activo' => 1],
            ['codigo' => 'RECARGO_KM_SAB_DOM', 'descripcion' => 'Recargo por kilómetros en sábados/domingos (PENDIENTE ALTA en la spec)', 'tipo_parametro' => 'PORCENTAJE', 'unidad' => '%', 'es_tecnico' => 0, 'activo' => 1],
            ['codigo' => 'RECARGO_HE_50', 'descripcion' => 'Multiplicador de horas extras al 50 % (PENDIENTE ALTA en la spec)', 'tipo_parametro' => 'PORCENTAJE', 'unidad' => '%', 'es_tecnico' => 0, 'activo' => 1],
            ['codigo' => 'RECARGO_HE_100', 'descripcion' => 'Multiplicador de horas extras al 100 % (PENDIENTE ALTA en la spec)', 'tipo_parametro' => 'PORCENTAJE', 'unidad' => '%', 'es_tecnico' => 0, 'activo' => 1],
        ];
        foreach ($extra as $p) {
            DB::table('motor_parametros')->insertOrIgnore($p + ['created_at' => $this->ahora, 'updated_at' => $this->ahora]);
        }
        $this->verificar('motor_parametros', 21);

        // Valores técnicos GENERAL, CONFIRMADO (configuración del motor, no normativa)
        foreach (['REDONDEO_DECIMALES' => '2', 'DIVISOR_DIAS' => '30', 'HORAS_MES_REFERENCIA' => '200'] as $codigo => $valor) {
            $id = DB::table('motor_parametros')->where('codigo', $codigo)->value('id_parametro');
            $existe = DB::table('motor_valores_parametros')->where('id_parametro', $id)->where('alcance', 'GENERAL')->exists();
            if (! $existe) {
                DB::table('motor_valores_parametros')->insert([
                    'id_parametro' => $id, 'alcance' => 'GENERAL', 'valor' => $valor, 'vigencia_desde' => '2026-01-01',
                    'estado_validacion' => 'CONFIRMADO', 'observaciones' => 'Parámetro técnico del motor (carga automática).',
                    'created_at' => $this->ahora, 'updated_at' => $this->ahora,
                ]);
            }
        }

        $this->bloque('motor_modelos_antiguedad', 'modelos_antiguedad', fn ($f) => [
            'id_modelo_antiguedad' => $f['id_modelo_antiguedad'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'],
            'metodo_calculo' => $f['metodo_calculo'], 'porcentaje_por_anio' => $f['porcentaje_por_anio'], 'porcentaje_maximo' => $f['porcentaje_maximo'],
            'version' => $f['version'] ?? 1, 'estado' => $f['estado'], 'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR',
            'activo' => (int) ($f['activo'] ?? 1), 'observaciones' => $f['observaciones'],
        ]);
        $this->bloque('motor_antiguedad_tramos', 'antiguedad_tramos', fn ($f) => [
            'id_tramo_antiguedad' => $f['id_tramo_antiguedad'], 'id_modelo_antiguedad' => $f['id_modelo_antiguedad'],
            'anios_desde' => $f['anios_desde'], 'anios_hasta' => $f['anios_hasta'], 'porcentaje' => $f['porcentaje'], 'orden' => $f['orden'],
        ]);
        $this->bloque('motor_modelos_vacaciones', 'modelos_vacaciones', fn ($f) => [
            'id_modelo_vacaciones' => $f['id_modelo_vacaciones'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'],
            'version' => $f['version'] ?? 1, 'estado' => $f['estado'], 'fuente' => $f['fuente'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_vacaciones_tramos', 'vacaciones_tramos', fn ($f) => [
            'id_tramo_vacaciones' => $f['id_tramo_vacaciones'], 'id_modelo_vacaciones' => $f['id_modelo_vacaciones'],
            'anios_desde' => $f['anios_desde'], 'incluye_desde' => (int) ($f['incluye_desde'] ?? 1), 'anios_hasta' => $f['anios_hasta'],
            'incluye_hasta' => $f['incluye_hasta'] === null ? null : (int) $f['incluye_hasta'], 'dias' => $f['dias'], 'orden' => $f['orden'],
        ]);
        $this->bloque('motor_tipos_novedad', 'tipos_novedad', fn ($f) => [
            'id_tipo_novedad' => $f['id_tipo_novedad'], 'codigo' => $f['codigo'], 'descripcion' => $f['descripcion'],
            'unidad' => $f['unidad'], 'activo' => (int) ($f['activo'] ?? 1),
        ]);
        $this->bloque('motor_bases_zonda', 'bases_zonda', fn ($f) => [
            'id_base_zonda' => $f['id_base_zonda'], 'codigo_base' => $f['codigo_base'], 'nombre' => $f['nombre'],
            'descripcion' => $f['descripcion'], 'activo' => (int) ($f['activo'] ?? 1),
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'observaciones' => $f['observaciones'],
        ]);
        $this->bloque('motor_conceptos', 'conceptos', fn ($f) => $this->concepto($f));
        $this->bloque('motor_base_zonda_incidencia', 'base_zonda_incidencia', fn ($f) => [
            'id_incidencia' => $f['id_incidencia'], 'id_base_zonda' => $f['id_base_zonda'], 'codigo_concepto' => $f['codigo_concepto'],
            'incidencia' => $f['incidencia'], 'vigencia_desde' => $f['vigencia_desde'], 'vigencia_hasta' => $f['vigencia_hasta'],
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'observaciones' => $f['observaciones'],
        ]);
        $this->bloque('motor_concepto_incidencia_arca', 'concepto_incidencia_arca', fn ($f) => [
            'id_incidencia_arca' => $f['id_incidencia_arca'], 'codigo_concepto' => $f['codigo_concepto'],
            'id_subsistema_aplicacion' => $f['id_subsistema_aplicacion'], 'incidencia' => $f['incidencia'],
            'vigencia_desde' => $f['vigencia_desde'], 'vigencia_hasta' => $f['vigencia_hasta'],
            'estado_validacion' => $f['estado_validacion'] ?? 'SIN_CONFIRMAR', 'fuente_normativa' => $f['fuente_normativa'],
            'observaciones' => $f['observaciones'],
        ]);
    }

    public function down(): void
    {
        // No se borra: son datos de referencia y pueden haber sido confirmados/ajustados por el usuario.
    }

    /**
     * Deriva la configuración de cálculo (estrategia + fuentes) de los 27 campos de la spec.
     * Es una heurística de carga inicial: el usuario la ajusta por pantalla; el mockup la exige
     * para poder liquidar sin DSL de fórmulas.
     */
    private function concepto(array $f): array
    {
        $sn = fn ($v) => (int) (strtoupper((string) $v) === 'S' || (string) $v === '1');
        $codigo = $f['codigo_concepto'];
        $modo = $f['modo_calculo'];
        $param = $f['codigo_parametro'];
        $novedad = $f['codigo_novedad_cantidad'];
        $unidad = (string) $f['unidad_principal'];

        $estrategia = null;
        $fCant = null;
        $fVal = null;
        $fImp = null;
        $baseZonda = null;
        if ($modo === 'MODULO_SISTEMA') {
            $estrategia = 'MODULO_SISTEMA';
            $fImp = 'MODULO_SISTEMA';
        } elseif ($codigo === 'BASICO') {
            $estrategia = 'BASICO';
            $fImp = 'FORMULA';
        } elseif ($codigo === 'ANTIG') {
            $estrategia = 'ANTIGUEDAD';
            $fImp = 'FORMULA';
        } elseif ($novedad !== null) {
            $estrategia = 'CANTIDAD_X_VALOR';
            $fCant = 'NOVEDAD';
            if ($unidad === 'HORAS') {
                $fVal = 'BASE_ZONDA';
                $baseZonda = 'BASE_HORA_NORMAL';
            }   // HE50, HE100, FERTRABHS
            elseif ($param !== null) {
                $fVal = 'PARAMETRO';
            }                                     // KMREC, CTRLCD, PERMFRB, …
            else {
                $fVal = 'BASICO';
            }                                                            // FERTRAB: valor día
        } elseif ($param !== null && $unidad === 'HORAS') {
            $estrategia = 'CANTIDAD_X_VALOR';
            $fCant = 'PARAMETRO';
            $fVal = 'BASE_ZONDA';
            $baseZonda = 'BASE_HORA_NORMAL'; // TOLHOR
        } elseif ($param !== null) {
            $estrategia = 'PORCENTAJE_SOBRE_BASE';
            $fVal = 'PARAMETRO';
            $baseZonda = $codigo === 'ADICNR20' ? 'BASE_NO_REMUNERATIVA' : null;                 // null = sobre el básico devengado
        } else {
            $estrategia = 'IMPORTE_DIRECTO';
            $fImp = 'MANUAL';                                   // ACTAFUT
        }

        return [
            'id_concepto' => $f['id_concepto'], 'codigo_concepto' => $codigo, 'nombre_concepto' => $f['nombre_concepto'],
            'alias_recibo' => $f['alias_recibo'], 'convenio_documental' => $f['convenio_documental'],
            'tipo_concepto' => $f['tipo_concepto'], 'naturaleza' => $f['naturaleza'], 'signo' => $f['signo'],
            'unidad_principal' => $unidad, 'modo_calculo' => $modo, 'modulo_responsable' => $f['modulo_responsable'],
            'base_modulo' => $f['base_modulo'], 'codigo_parametro' => $param, 'codigo_novedad_cantidad' => $novedad,
            'id_formula_base' => null,
            'requiere_novedad' => $sn($f['requiere_novedad']), 'editable_manual' => $sn($f['editable_manual']),
            'repetible' => $sn($f['repetible']), 'permite_importe_cero' => $sn($f['permite_importe_cero']),
            'imprime_recibo' => $sn($f['imprime_recibo']), 'es_concepto_sistema' => $sn($f['es_concepto_sistema']),
            'tipo_repeticion' => $f['tipo_repeticion'], 'orden_visual_sugerido' => $f['orden_visual_sugerido'],
            'vigencia_desde' => $f['vigencia_desde'] ?? '2026-07-01', 'vigencia_hasta' => $f['vigencia_hasta'],
            'activo' => (int) ($f['activo'] ?? 1), 'observaciones_funcionales' => $f['observaciones_funcionales'],
            'estrategia_calculo' => $estrategia, 'fuente_cantidad' => $fCant, 'fuente_valor' => $fVal, 'fuente_importe' => $fImp,
            'codigo_base_zonda' => $baseZonda, 'regla_redondeo' => 'PARAMETRIZADA',
            'politica_edicion' => $sn($f['editable_manual']) ? 'EDITABLE' : 'NO_EDITABLE',
            'estado_configuracion' => 'PUBLICADO',
        ];
    }

    /** Inserta un bloque desde database/seeders/motor/datos/{archivo}.php y verifica el conteo. */
    private function bloque(string $tabla, string $archivo, Closure $mapa): void
    {
        $filas = require database_path("seeders/motor/datos/{$archivo}.php");
        foreach ($filas as $f) {
            DB::table($tabla)->insertOrIgnore($mapa($f) + ['created_at' => $this->ahora, 'updated_at' => $this->ahora]);
        }
        $this->verificar($tabla, count($filas));
    }

    private function verificar(string $tabla, int $esperado): void
    {
        $real = DB::table($tabla)->count();
        if ($real < $esperado) {
            throw new RuntimeException("Seed del motor: {$tabla} tiene {$real} filas y se esperaban al menos {$esperado}. INSERT IGNORE ocultó una violación de FK o de clave: revisar los datos.");
        }
    }
};
