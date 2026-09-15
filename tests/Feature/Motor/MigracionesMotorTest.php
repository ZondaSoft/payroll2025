<?php

namespace Tests\Feature\Motor;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

final class MigracionesMotorTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
    }

    public function test_crea_las_41_tablas_del_motor(): void
    {
        $tablas = array_filter(Schema::getTableListing(), fn (string $t) => str_starts_with(substr($t, (int) strrpos('.'.$t, '.')), 'motor_'));

        self::assertCount(41, $tablas, implode(', ', $tablas));
    }

    public function test_seed_normativo_carga_los_conteos_del_excel(): void
    {
        $esperado = [
            'motor_bases_arca' => 10, 'motor_subsistemas' => 8, 'motor_subsistema_aplicacion' => 13,
            'motor_encuadramientos_patronales' => 2, 'motor_componentes_tope' => 4, 'motor_condiciones_arca' => 7,
            'motor_modalidades_arca' => 2, 'motor_situaciones_revista_arca' => 4,
            'motor_alicuotas_contribuciones' => 8, 'motor_alicuotas_aportes' => 4, 'motor_topes_minimos' => 7,
            'motor_regimenes_contributivos' => 9, 'motor_parametros' => 21, 'motor_valores_parametros' => 3,
            'motor_modelos_antiguedad' => 2, 'motor_antiguedad_tramos' => 31, 'motor_modelos_vacaciones' => 1,
            'motor_vacaciones_tramos' => 4, 'motor_tipos_novedad' => 13, 'motor_bases_zonda' => 4,
            'motor_conceptos' => 37, 'motor_base_zonda_incidencia' => 148, 'motor_concepto_incidencia_arca' => 481,
        ];
        foreach ($esperado as $tabla => $n) {
            self::assertSame($n, DB::table($tabla)->count(), $tabla);
        }
    }

    public function test_seed_es_idempotente(): void
    {
        $antes = DB::table('motor_concepto_incidencia_arca')->count();
        $this->artisan('migrate', ['--path' => database_path('migrations/motor'), '--realpath' => true, '--force' => true]);

        self::assertSame($antes, DB::table('motor_concepto_incidencia_arca')->count());
    }

    public function test_reglas_de_negocio_reflejadas_en_los_datos(): void
    {
        // Solo REM_1, REM_4 y REM_5 admiten tope (spec TOPES_MINIMOS)
        self::assertSame(['REM_1', 'REM_4', 'REM_5'], DB::table('motor_bases_arca')->where('admite_tope', 1)->orderBy('codigo_base')->pluck('codigo_base')->all());
        // TODAS se persiste como comodín NULL
        self::assertSame(2, DB::table('motor_alicuotas_aportes')->whereNull('codigo_condicion')->count());
        // TOP-006/007 sin importe → SIN_DATO
        self::assertSame(2, DB::table('motor_topes_minimos')->whereNull('importe')->where('estado_validacion', 'SIN_DATO')->count());
        // Ningún dato normativo nace CONFIRMADO salvo los 3 parámetros técnicos
        self::assertSame(0, DB::table('motor_alicuotas_contribuciones')->where('estado_validacion', 'CONFIRMADO')->count());
        self::assertSame(3, DB::table('motor_valores_parametros')->where('estado_validacion', 'CONFIRMADO')->count());
        // 216 incidencias PENDIENTE del Excel se conservan tal cual
        self::assertSame(216, DB::table('motor_concepto_incidencia_arca')->where('incidencia', 'PENDIENTE')->count());
        // Estrategias derivadas: los 13 MODULO_SISTEMA y el básico
        self::assertSame(13, DB::table('motor_conceptos')->where('estrategia_calculo', 'MODULO_SISTEMA')->count());
        self::assertSame('BASICO', DB::table('motor_conceptos')->where('codigo_concepto', 'BASICO')->value('estrategia_calculo'));
        self::assertSame('BASE_HORA_NORMAL', DB::table('motor_conceptos')->where('codigo_concepto', 'HE50')->value('codigo_base_zonda'));
    }
}
