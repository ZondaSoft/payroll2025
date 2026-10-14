<?php

namespace Tests\Support\Motor;

use Illuminate\Support\Facades\DB;

/**
 * El empleado testigo del libro docente del CCT 371/03: Operario de Playa, 14 años
 * de antigüedad, básico de agosto 2026, con manejo de fondos y 9 horas de
 * tolerancia horaria.
 *
 * Es el legajo con el que los 13 casos del libro (P01 a P13) explican una regla
 * cada uno. Los valores salen de la hoja «Parametros» del libro
 * (CCT371_Libro_Docente_Operario_Playa_082026_v3.xlsx, SHA-256 809745ac…).
 */
final class EscenarioLibroDocente
{
    public const PERIODO = '202608';

    public const RL = 30;

    public const CATEGORIA = 420;

    /** Parametros!B43 — LESTAC-BLEXA-08-2026 CAR.xlsm, legajo testigo. */
    public const BASICO = '1350389.10';

    /** Parametros!B17 — años cumplidos al 31/08/2026 (ingreso 2011-10-21). */
    public const ANTIGUEDAD_ANIOS = 14;

    /** Parametros!B23 — Res. 231/2007. */
    public const HORAS_TOLERANCIA = '9';

    public static function sembrar(): void
    {
        $ahora = now()->toDateTimeString();

        DB::table('sue086s')->insertOrIgnore([
            ['id' => EscenarioLibro::EMPRESA, 'codigo' => '001', 'detalle' => 'PETROANDINA SA', 'cuit' => '30700000006'],
        ]);
        DB::table('sue007s')->insertOrIgnore([
            ['id' => EscenarioLibro::CONVENIO_371, 'codigo' => '4', 'detalle' => 'Estaciones de Servicio'],
        ]);
        DB::table('sue006s')->insertOrIgnore([
            ['id' => self::CATEGORIA, 'codigo' => '0420', 'detalle' => 'Operario de Playa', 'cod_conve' => '4'],
        ]);
        DB::table('sue010s')->insertOrIgnore([
            ['id' => 1, 'detalle' => 'Jornada completa', 'horas_semana' => 48, 'parcial' => 0],
        ]);
        DB::table('sue001s')->insertOrIgnore([
            ['id' => 15700, 'codigo' => 8001, 'detalle' => 'TESTIGO', 'nombres' => 'OPERARIO DE PLAYA',
                'cuil' => '20317535700', 'grupo_emp' => '01', 'cod_categ' => '0420', 'convenio' => '4',
                'jornada_id' => 1, 'alta' => '2011-10-21'],
        ]);

        DB::table('motor_relacion_laboral')->insertOrIgnore([
            'relacion_laboral_id' => self::RL, 'id_legajo' => 15700, 'empresa_id' => EscenarioLibro::EMPRESA,
            'legajo' => '8001', 'cuil' => '20317535700', 'apellido_nombre' => 'TESTIGO OPERARIO DE PLAYA',
            'fecha_ingreso' => '2011-10-21', 'estado' => 'VIGENTE', 'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        DB::table('motor_relacion_laboral_categoria')->insertOrIgnore([
            'relacion_laboral_id' => self::RL, 'id_categoria' => self::CATEGORIA, 'codigo_categoria' => '0420',
            'vigencia_desde' => '2011-10-21', 'estado_validacion' => 'CONFIRMADO',
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        DB::table('motor_basicos')->insertOrIgnore([
            'id_categoria' => self::CATEGORIA, 'codigo_categoria' => '0420', 'importe_basico' => self::BASICO,
            'vigencia_desde' => '2026-08-01', 'estado_validacion' => 'CONFIRMADO',
            'fuente_normativa' => 'LESTAC-BLEXA-08-2026 CAR.xlsm · LQNOR legajo testigo',
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        self::divisores($ahora);
        self::parametros($ahora);
        self::tolerancia($ahora);
        self::modeloAntiguedad($ahora);
        self::perfil($ahora);
    }

    private static function divisores(string $ahora): void
    {
        foreach ([
            // Divisor de jornales del 371/03: el mismo 25 que LCT 155 a) usa para
            // vacaciones, y con el que el libro calcula el feriado y el día de la actividad.
            ['DIVJOR', '25', 'CCT 371/03 — jornales del mes (P-10-b)'],
            ['HMJORN', '200', 'Divisor de hora normal del libro Blexa / escala histórica'],
            ['HSJORN', '48', 'CCT 371/03 — jornada completa semanal'],
            ['HDJORN', '8', 'CCT 371/03 — horas diarias de la jornada'],
        ] as [$codigo, $valor, $fuente]) {
            DB::table('motor_valores_variable')->insertOrIgnore([
                'codigo' => $codigo, 'alcance' => 'CONVENIO', 'id_convenio' => EscenarioLibro::CONVENIO_371,
                'valor' => $valor, 'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                'fuente' => $fuente, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private static function parametros(string $ahora): void
    {
        foreach ([
            ['PORC_ASISTENCIA', '0.08', 'CCT 371/03 art. 13'],
            ['PORC_MANEJO_FONDOS', '0.08', 'CCT 371/03 art. 12'],
            // El libro es explícito: «una falta injustificada elimina el premio»
            // (art. 13). El tope de inasistencias es cero, no uno.
            ['INASIS_MAX', '0', 'CCT 371/03 art. 13: una falta injustificada elimina el premio'],
            ['LLEGTAR_MAX', '0', 'CCT 371/03 art. 13'],
            ['SUSP_MAX', '0', 'Criterio operativo del libro fuente: la suspensión también hace perder el premio'],
        ] as [$codigo, $valor, $fuente]) {
            $id = DB::table('motor_parametros')->where('codigo', $codigo)->value('id_parametro');
            if ($id === null) {
                continue;
            }
            DB::table('motor_valores_parametros')->insertOrIgnore([
                'id_parametro' => $id, 'alcance' => 'CONVENIO', 'id_convenio' => EscenarioLibro::CONVENIO_371,
                'valor' => $valor, 'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private static function tolerancia(string $ahora): void
    {
        $id = DB::table('motor_categoria_concepto')
            ->where('id_categoria', self::CATEGORIA)->where('codigo_concepto', 'TOLHOR')->value('id_categoria_concepto');
        if ($id === null) {
            $id = DB::table('motor_categoria_concepto')->insertGetId([
                'id_categoria' => self::CATEGORIA, 'codigo_concepto' => 'TOLHOR', 'tipo_valor' => 'CANTIDAD',
                'unidad_valor' => 'HORAS', 'activo' => true, 'estado_validacion' => 'CONFIRMADO',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ], 'id_categoria_concepto');
        }
        DB::table('motor_categoria_concepto_vigencia')->insertOrIgnore([
            'id_categoria_concepto' => $id, 'valor' => self::HORAS_TOLERANCIA, 'vigencia_desde' => '2026-01-01',
            'estado_validacion' => 'CONFIRMADO', 'fuente' => 'Res. 231/2007 — 9 horas mensuales',
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    private static function modeloAntiguedad(string $ahora): void
    {
        $id = DB::table('motor_modelos_antiguedad')->where('codigo', 'ANTIG_2_3_TOPE70_V1')->value('id_modelo_antiguedad');
        DB::table('motor_convenio_modelo_antiguedad')->insertOrIgnore([
            'id_convenio' => EscenarioLibro::CONVENIO_371, 'id_modelo_antiguedad' => $id,
            'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    /** El perfil del testigo: los conceptos del 371/03 que este puesto cobra. */
    private static function perfil(string $ahora): void
    {
        $aplican = [
            'BASICO' => 'JORNADA_PACTADA', 'ANTIG' => 'UNICA', 'ASIST' => 'UNICA', 'MANFON' => 'UNICA',
            'TOLHOR' => 'UNICA', 'HE50' => 'UNICA', 'HE100' => 'UNICA', 'FERTRAB' => 'UNICA',
            'FERTRABHS' => 'UNICA', 'DIAACT' => 'UNICA', 'INASIS' => 'UNICA', 'SUSPEN' => 'UNICA',
            'SINGOCE' => 'UNICA', 'VACGOZ' => 'UNICA',
        ];
        $noAplican = [
            'ADTIT' => 'El legajo testigo no acredita título habilitante.',
            'ACTAFUT' => 'Acta futura: no corresponde a este convenio.',
            'AD562' => 'El acuerdo 5/6/2 es del CCT 40/89.',
            'AD564' => 'El adicional de tareas es del CCT 40/89.',
            'ACDSIND562' => 'El acuerdo 5/6/2 es del CCT 40/89.',
            'ADICNR20' => 'Adicional no remunerativo del CCT 40/89.',
            'KMREC' => 'Kilometraje: concepto del CCT 40/89.',
            'KMSABDOM' => 'Kilometraje: concepto del CCT 40/89.',
            'VIATKM' => 'Viáticos por km: concepto del CCT 40/89.',
            'CTRLCD' => 'Control de carga y descarga: concepto del CCT 40/89.',
            'PERMFRA' => 'Permanencia fuera de residencia: concepto del CCT 40/89.',
            'PERMFRB' => 'Permanencia fuera de residencia: concepto del CCT 40/89.',
            'PERNOCT' => 'Pernoctada: concepto del CCT 40/89.',
            'PLUSPLUR' => 'Plus por pluralidad: concepto del CCT 40/89.',
            'COMIDA' => 'Comida: concepto del CCT 40/89.',
            'VIATESP' => 'Viático especial: concepto del CCT 40/89.',
            'INAVAC' => 'Criterio anterior: el libro docente v3 usa sueldo proporcional, no una línea negativa por vacaciones.',
        ];

        $id = DB::table('motor_perfil')->where('codigo_perfil', 'TESTIGO_PLAYA')->value('id_perfil');
        if ($id === null) {
            $id = DB::table('motor_perfil')->insertGetId([
                'codigo_perfil' => 'TESTIGO_PLAYA', 'nombre' => 'Libro docente — Operario de Playa 371/03',
                'nivel' => 'CATEGORIA', 'orden_precedencia' => 3, 'id_categoria' => self::CATEGORIA,
                'vigencia_desde' => '2026-01-01', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ], 'id_perfil');
        }
        foreach ($aplican as $concepto => $variante) {
            DB::table('motor_perfil_concepto')->insertOrIgnore([
                'id_perfil' => $id, 'codigo_concepto' => $concepto, 'aplica' => true, 'variante' => $variante,
                'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
        foreach ($noAplican as $concepto => $motivo) {
            DB::table('motor_perfil_concepto')->insertOrIgnore([
                'id_perfil' => $id, 'codigo_concepto' => $concepto, 'aplica' => false, 'motivo' => $motivo,
                'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    /** @param array<string, string> $novedades */
    public static function novedades(array $novedades): void
    {
        EscenarioLibro::novedades(self::RL, self::PERIODO, $novedades);
    }
}
