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

    public function test_crea_las_tablas_del_motor(): void
    {
        // Schema::getTableListing() en MySQL puede listar todos los schemas visibles
        // (swnov también tiene tablas motor_*), así que se filtra por la base de tests.
        $base = DB::getDatabaseName();
        $tablas = array_filter(
            array_map(fn (string $t) => str_contains($t, '.') ? explode('.', $t, 2) : [$base, $t], Schema::getTableListing()),
            fn (array $p) => $p[0] === $base && str_starts_with($p[1], 'motor_')
        );

        // 41 del modelo original + 20 del alineamiento con el DBML v2 (fórmulas,
        // normativa de Etapa 3A y persistencia de resultados).
        self::assertCount(61, $tablas, implode(', ', array_column($tablas, 1)));
    }

    public function test_seed_normativo_carga_los_conteos_del_excel(): void
    {
        // Conteos del Excel funcional v5 (DBML v2, auditoría ARCA 2026-09-25).
        $esperado = [
            'motor_bases_arca' => 10, 'motor_subsistemas' => 8, 'motor_subsistema_aplicacion' => 13,
            'motor_encuadramientos_patronales' => 2, 'motor_componentes_tope' => 4, 'motor_condiciones_arca' => 7,
            'motor_modalidades_arca' => 4, 'motor_situaciones_revista_arca' => 4,
            'motor_alicuotas_contribuciones' => 8, 'motor_alicuotas_aportes' => 4, 'motor_topes_minimos' => 9,
            'motor_regimenes_contributivos' => 9, 'motor_regimen_distribucion' => 4,
            'motor_reglas_base_diferencial_arca' => 2, 'motor_parametros' => 22,
            'motor_modelos_antiguedad' => 2, 'motor_antiguedad_tramos' => 31, 'motor_modelos_vacaciones' => 1,
            'motor_vacaciones_tramos' => 4, 'motor_tipos_novedad' => 15, 'motor_bases_zonda' => 5,
            'motor_conceptos' => 40, 'motor_base_zonda_incidencia' => 185, 'motor_concepto_incidencia_arca' => 507,
            'motor_variables' => 8, 'motor_modulos' => 3, 'motor_modulo_dependencia' => 2,
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
        // La auditoría del 2026-09-25 cerró las 507 incidencias en S o N: ninguna queda sin decidir
        self::assertSame(0, DB::table('motor_concepto_incidencia_arca')->where('incidencia', 'PENDIENTE')->count());
        self::assertSame(507, DB::table('motor_concepto_incidencia_arca')->where('estado_validacion', 'CONFIRMADO')->count());
        // ACTAFUT queda deliberadamente fuera de la matriz: debe bloquear si se usa
        self::assertSame(0, DB::table('motor_concepto_incidencia_arca')->where('codigo_concepto', 'ACTAFUT')->count());
        // El RIFL ya no es un 5 % opaco: se distribuye entre cuatro subsistemas y suma exactamente 5 %
        self::assertSame('0.050000', (string) DB::table('motor_regimen_distribucion')->sum('porcentaje'));
        // RENATRE sustituye el subsistema FNE completo
        self::assertSame('FNE', DB::table('motor_regimenes_contributivos')->where('codigo', 'RURAL_RENATRE')->value('reemplaza_subsistema'));
        // El tope máximo se prorratea por días; el mínimo pasó a REM_10 (piso previsional)
        self::assertSame(6, DB::table('motor_topes_minimos')->where('proporcional_dias', 1)->count());
        self::assertSame(['REM_10', 'REM_10'], DB::table('motor_topes_minimos')->where('tipo_tope', 'MINIMO')->pluck('codigo_base')->all());
        // Unidad pendiente en el origen: no se infiere
        self::assertSame(2, DB::table('motor_tipos_novedad')->whereNull('unidad')->count());
    }
}
