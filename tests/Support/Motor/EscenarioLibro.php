<?php

namespace Tests\Support\Motor;

use Illuminate\Support\Facades\DB;

/**
 * Siembra, sobre el esquema de tests, la configuración del libro de Oscar para el
 * legajo 7019 del CCT 371/03 (y lo mínimo del 221 / CCT 40/89).
 *
 * Es la parte que falta en `swnov` y que el cliente cargará por el ABM: los
 * divisores del convenio, el básico de la categoría, los valores de parámetros y
 * los perfiles con sus variantes. Los conceptos, las fórmulas y las bases ya los
 * trae la migración del v5, así que lo que se prueba acá es que esa configuración
 * alcance para liquidar al centavo.
 *
 * Los importes salen de sim/config_7019.json, el caso que Oscar cerró contra el
 * recibo de 07/2026.
 */
final class EscenarioLibro
{
    public const CONVENIO_371 = 14;

    public const CONVENIO_4089 = 10;

    public const CATEGORIA_7019 = 411;

    public const CATEGORIA_221 = 412;

    public const EMPRESA = 6;

    public const RL_7019 = 18;

    public const RL_221 = 19;

    public const BASICO_7019 = '1335827.18';

    public const BASICO_221 = '1060010.29';

    public static function sembrar(): void
    {
        self::legacy();
        self::relaciones();
        self::divisoresDeConvenio();
        self::valoresDeParametros();
        self::basicos();
        self::modeloDeAntiguedad();
        self::perfiles();
    }

    private static function legacy(): void
    {
        DB::table('sue086s')->insertOrIgnore([
            ['id' => self::EMPRESA, 'codigo' => '001', 'detalle' => 'PETROANDINA SA', 'cuit' => '30700000006'],
        ]);
        DB::table('sue007s')->insertOrIgnore([
            ['id' => self::CONVENIO_371, 'codigo' => '4', 'detalle' => 'Estaciones de Servicio'],
            ['id' => self::CONVENIO_4089, 'codigo' => '2', 'detalle' => 'Transporte'],
        ]);
        DB::table('sue006s')->insertOrIgnore([
            ['id' => self::CATEGORIA_7019, 'codigo' => '0089', 'detalle' => 'Operario Int. y Anexo', 'cod_conve' => '4'],
            ['id' => self::CATEGORIA_221, 'codigo' => '0201', 'detalle' => 'Conductor de primera', 'cod_conve' => '2'],
        ]);
        // Jornada de 48 horas semanales: es la que hace que BASICO con variante
        // JORNADA_PACTADA no prorratee (HSPACT 48 / HSJORN 48 = 1).
        DB::table('sue010s')->insertOrIgnore([
            ['id' => 1, 'detalle' => 'Jornada completa', 'horas_semana' => 48, 'parcial' => 0],
        ]);
        DB::table('sue001s')->insertOrIgnore([
            ['id' => 15630, 'codigo' => 7019, 'detalle' => 'AGUIRRE', 'nombres' => 'ENRIQUE ISMAEL', 'cuil' => '20317535644',
                'grupo_emp' => '01', 'cod_categ' => '0089', 'convenio' => '4', 'jornada_id' => 1, 'alta' => '2018-06-11'],
            ['id' => 15631, 'codigo' => 221, 'detalle' => 'ALFARO', 'nombres' => 'SERGIO RODRIGO', 'cuil' => '20317535645',
                'grupo_emp' => '01', 'cod_categ' => '0201', 'convenio' => '2', 'jornada_id' => 1, 'alta' => '2009-07-01'],
        ]);
    }

    private static function relaciones(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            [self::RL_7019, 15630, '7019', '20317535644', 'AGUIRRE ENRIQUE ISMAEL', '2018-06-11', self::CATEGORIA_7019, '0089'],
            [self::RL_221, 15631, '221', '20317535645', 'ALFARO SERGIO RODRIGO', '2009-07-01', self::CATEGORIA_221, '0201'],
        ] as [$rl, $legajo, $codigo, $cuil, $nombre, $ingreso, $categoria, $codCategoria]) {
            DB::table('motor_relacion_laboral')->insertOrIgnore([
                'relacion_laboral_id' => $rl, 'id_legajo' => $legajo, 'empresa_id' => self::EMPRESA, 'legajo' => $codigo,
                'cuil' => $cuil, 'apellido_nombre' => $nombre, 'fecha_ingreso' => $ingreso, 'estado' => 'VIGENTE',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
            DB::table('motor_relacion_laboral_categoria')->insertOrIgnore([
                'relacion_laboral_id' => $rl, 'id_categoria' => $categoria, 'codigo_categoria' => $codCategoria,
                'vigencia_desde' => $ingreso, 'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    /**
     * Los divisores normativos del convenio. No se derivan uno del otro (P-10-b):
     * el 371/03 tiene 25 jornales y 200 horas mensuales, el 40/89 tiene 24 y 192.
     */
    private static function divisoresDeConvenio(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            ['DIVJOR', self::CONVENIO_371, '25', 'CCT 371/03 — jornales del mes (P-10-b)'],
            ['HMJORN', self::CONVENIO_371, '200', 'CCT 371/03 — horas mensuales de la jornada'],
            ['HSJORN', self::CONVENIO_371, '48', 'CCT 371/03 — jornada completa semanal'],
            ['HDJORN', self::CONVENIO_371, '8', 'CCT 371/03 — horas diarias de la jornada'],
            ['DIVJOR', self::CONVENIO_4089, '24', 'CCT 40/89 ítem 6.1.6 — jornales del mes'],
            ['HMJORN', self::CONVENIO_4089, '192', 'CCT 40/89 — horas mensuales de la jornada'],
            ['HSJORN', self::CONVENIO_4089, '48', 'CCT 40/89 — jornada completa semanal'],
            ['HDJORN', self::CONVENIO_4089, '8', 'CCT 40/89 — horas diarias de la jornada'],
        ] as [$codigo, $convenio, $valor, $fuente]) {
            DB::table('motor_valores_variable')->insertOrIgnore([
                'codigo' => $codigo, 'alcance' => 'CONVENIO', 'id_convenio' => $convenio, 'valor' => $valor,
                'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO', 'fuente' => $fuente,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private static function valoresDeParametros(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            ['PORC_ASISTENCIA', self::CONVENIO_371, '0.08'],
            ['PORC_MANEJO_FONDOS', self::CONVENIO_371, '0.08'],
            ['INASIS_MAX', self::CONVENIO_371, '1'],
            ['LLEGTAR_MAX', self::CONVENIO_371, '2'],
            ['TARIFA_KM', self::CONVENIO_4089, '84.80265'],
            ['PORC_ADIC_BASICO_5_6_2', self::CONVENIO_4089, '0.2'],
            ['PORC_ADIC_NO_REMUNERATIVO', self::CONVENIO_4089, '0.2'],
            ['PORC_ACUERDO_EMPRESA_SINDICATO_5_6_2', self::CONVENIO_4089, '0.2'],
            ['VALOR_CONTROL_CARGA_DESCARGA', self::CONVENIO_4089, '44167.1'],
            ['VALOR_PERMANENCIA_FR_B', self::CONVENIO_4089, '30004.53'],
            ['VALOR_PERMANENCIA_FR_A', self::CONVENIO_4089, '57242.2'],
            ['VALOR_PERNOCTADA_BASE', self::CONVENIO_4089, '18891.63'],
        ] as [$codigo, $convenio, $valor]) {
            $id = DB::table('motor_parametros')->where('codigo', $codigo)->value('id_parametro');
            if ($id === null) {
                continue;
            }
            DB::table('motor_valores_parametros')->insertOrIgnore([
                'id_parametro' => $id, 'alcance' => 'CONVENIO', 'id_convenio' => $convenio, 'valor' => $valor,
                'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private static function basicos(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            [self::CATEGORIA_7019, self::BASICO_7019, 'Escala CCT 371/03 vigente 07/2026'],
            [self::CATEGORIA_221, self::BASICO_221, 'Escala CCT 40/89 vigente 07/2026'],
        ] as [$categoria, $importe, $fuente]) {
            DB::table('motor_basicos')->insertOrIgnore([
                'id_categoria' => $categoria, 'codigo_categoria' => $categoria === self::CATEGORIA_7019 ? '0089' : '0201',
                'importe_basico' => $importe, 'vigencia_desde' => '2026-07-01',
                'estado_validacion' => 'CONFIRMADO', 'fuente_normativa' => $fuente,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    /**
     * Asigna los modelos de antigüedad que el v5 ya trae. El 7019 tiene 8 años
     * cumplidos y el modelo de escala del 371/03 le da 16 % (2 % por año hasta los
     * 20, 3 % después, con tope del 70 %) — el porcentaje con el que cierra el recibo.
     */
    private static function modeloDeAntiguedad(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            [self::CONVENIO_371, 'ANTIG_2_3_TOPE70_V1'],
            [self::CONVENIO_4089, 'ANTIG_1_PCT_ANUAL_V1'],
        ] as [$convenio, $codigo]) {
            $id = DB::table('motor_modelos_antiguedad')->where('codigo', $codigo)->value('id_modelo_antiguedad');
            if ($id === null) {
                continue;
            }
            DB::table('motor_convenio_modelo_antiguedad')->insertOrIgnore([
                'id_convenio' => $convenio, 'id_modelo_antiguedad' => $id, 'vigencia_desde' => '2026-01-01',
                'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    /**
     * Los perfiles del v5, con el detalle que decide el caso: el 7019 no cobra
     * manejo de fondos (regla de nivel TRABAJADOR) y el acuerdo 5/6/2 es del 40/89.
     */
    private static function perfiles(): void
    {
        $ahora = now()->toDateTimeString();
        $perfiles = [
            ['CCT_371_03', 'Convenio CCT 371/03 — Estaciones de Servicio', 'CONVENIO', 4,
                ['id_convenio' => self::CONVENIO_371], [
                    'BASICO' => [true, 'JORNADA_PACTADA', 'Jornada pactada individual (DSL §22).'],
                    'ANTIG' => [true, 'UNICA', null],
                    'ASIST' => [true, 'UNICA', 'Concepto del CCT 371/03.'],
                    'MANFON' => [true, 'UNICA', null],
                    'HE50' => [true, 'UNICA', null],
                    'HE100' => [true, 'UNICA', null],
                    'FERTRAB' => [true, 'UNICA', null],
                    'FERTRABHS' => [true, 'UNICA', null],
                    'TOLHOR' => [true, 'UNICA', null],
                    'ADTIT' => [true, 'UNICA', 'Adicional por título del CCT 371/03.'],
                    'ACTAFUT' => [false, null, 'Acta futura: no corresponde a este convenio.'],
                    'AD562' => [false, null, 'El acuerdo 5/6/2 es del CCT 40/89.'],
                    'ACDSIND562' => [false, null, 'El acuerdo 5/6/2 es del CCT 40/89.'],
                    'ADICNR20' => [false, null, 'Adicional no remunerativo del CCT 40/89.'],
                    'KMREC' => [false, null, 'Kilometraje: concepto del CCT 40/89.'],
                    'KMSABDOM' => [false, null, 'Kilometraje: concepto del CCT 40/89.'],
                    'VIATKM' => [false, null, 'Viáticos por km: concepto del CCT 40/89.'],
                    'CTRLCD' => [false, null, 'Control de carga y descarga: concepto del CCT 40/89.'],
                    'PERMFRA' => [false, null, 'Permanencia fuera de residencia: concepto del CCT 40/89.'],
                    'PERMFRB' => [false, null, 'Permanencia fuera de residencia: concepto del CCT 40/89.'],
                    'PERNOCT' => [false, null, 'Pernoctada: concepto del CCT 40/89.'],
                    'PLUSPLUR' => [false, null, 'Plus por pluralidad: concepto del CCT 40/89.'],
                ]],
            ['LEGAJO_7019', 'Trabajador — legajo 7019', 'TRABAJADOR', 1,
                ['relacion_laboral_id' => self::RL_7019], [
                    'MANFON' => [false, null, 'El legajo no tiene manejo de fondos.'],
                    'ADTIT' => [false, null, 'El legajo no acredita título habilitante.'],
                    'TOLHOR' => [false, null, 'Sin tolerancia horaria cargada para la categoría.'],
                ]],
            ['CCT_40_89', 'Convenio CCT 40/89 — Camioneros', 'CONVENIO', 4,
                ['id_convenio' => self::CONVENIO_4089], [
                    'BASICO' => [true, 'LARGA_DISTANCIA', 'Larga distancia: básico de escala sin prorrateo.'],
                    'ANTIG' => [true, 'UNICA', null],
                    'KMREC' => [true, 'UNICA', null],
                    'KMSABDOM' => [true, 'UNICA', null],
                    'CTRLCD' => [true, 'UNICA', null],
                    'PERMFRA' => [true, 'UNICA', null],
                    'PERMFRB' => [true, 'UNICA', null],
                    'PERNOCT' => [true, 'UNICA', null],
                    'VIATKM' => [true, 'UNICA', null],
                    'AD562' => [true, 'UNICA', null],
                    'ACDSIND562' => [true, 'UNICA', null],
                    'ADICNR20' => [true, 'UNICA', null],
                    'FERTRAB' => [true, 'UNICA', null],
                    'FERTRABHS' => [true, 'UNICA', null],
                    // H-4: en larga distancia las horas extras no corresponden. Es una
                    // exclusión estructural del perfil, no una novedad que falta.
                    'HE50' => [false, null, 'En larga distancia las horas extras no corresponden (H-4).'],
                    'HE100' => [false, null, 'En larga distancia las horas extras no corresponden (H-4).'],
                    'ASIST' => [false, null, 'Premio asistencia: concepto del CCT 371/03.'],
                    'MANFON' => [false, null, 'Manejo de fondos: concepto del CCT 371/03.'],
                    'TOLHOR' => [false, null, 'Tolerancia horaria: concepto del CCT 371/03.'],
                    'ADTIT' => [false, null, 'Adicional por título: concepto del CCT 371/03.'],
                    'ACTAFUT' => [false, null, 'Acta futura: no corresponde a este convenio.'],
                    'PLUSPLUR' => [false, null, 'Sin valor por categoría cargado para este legajo.'],
                ]],
        ];

        foreach ($perfiles as [$codigo, $nombre, $nivel, $precedencia, $ambito, $reglas]) {
            $id = DB::table('motor_perfil')->where('codigo_perfil', $codigo)->value('id_perfil');
            if ($id === null) {
                $id = DB::table('motor_perfil')->insertGetId([
                    'codigo_perfil' => $codigo, 'nombre' => $nombre, 'nivel' => $nivel,
                    'orden_precedencia' => $precedencia, 'vigencia_desde' => '2026-01-01', 'activo' => true,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ] + $ambito, 'id_perfil');
            }
            foreach ($reglas as $concepto => [$aplica, $variante, $motivo]) {
                DB::table('motor_perfil_concepto')->insertOrIgnore([
                    'id_perfil' => $id, 'codigo_concepto' => $concepto, 'aplica' => $aplica, 'variante' => $variante,
                    'motivo' => $motivo, 'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO',
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        }
    }

    /** Novedades del período para una relación: código => cantidad. */
    public static function novedades(int $relacionLaboralId, string $periodo, array $novedades): void
    {
        $ahora = now()->toDateTimeString();
        $canonico = (string) \App\Motor\Dominio\ValorObjetos\Periodo::de($periodo);
        foreach ($novedades as $codigo => $cantidad) {
            DB::table('motor_novedades')->insert([
                'periodo' => $canonico, 'relacion_laboral_id' => $relacionLaboralId, 'codigo_novedad' => $codigo,
                'cantidad' => $cantidad, 'origen' => 'FIXTURE', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }
}
