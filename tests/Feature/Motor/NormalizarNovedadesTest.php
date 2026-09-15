<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\NormalizarNovedades;
use App\Motor\Aplicacion\CasosDeUso\SincronizarRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

final class NormalizarNovedadesTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        DB::table('sue086s')->insert(['id' => 6, 'codigo' => '05', 'detalle' => 'BLEXA']);
        DB::table('sue001s')->insert(['id' => 15579, 'codigo' => 7000, 'detalle' => 'ALEMAN', 'nombres' => 'CECILIA', 'alta' => '2022-07-01', 'grupo_emp' => '05', 'sicoss_condi' => 1, 'sicoss_modal' => 8]);
        app(SincronizarRelacionesLaborales::class)->ejecutar('05', Periodo::de('2026-07'));
        DB::table('sue028s')->insert([
            ['id' => 1, 'periodo' => '202607', 'legajo' => 7000, 'cod_nov' => 'HSEX50', 'cantidad' => '9.50', 'int_vac' => 0],
            ['id' => 2, 'periodo' => '202607', 'legajo' => 7000, 'cod_nov' => 'HSEX50', 'cantidad' => '2.00', 'int_vac' => 0],
            ['id' => 3, 'periodo' => '202607', 'legajo' => 7000, 'cod_nov' => 'PRESTA', 'cantidad' => '150000.00', 'int_vac' => 0],
            ['id' => 4, 'periodo' => '202607', 'legajo' => 7000, 'cod_nov' => 'VACAC', 'cantidad' => '1.00', 'int_vac' => 7],
            ['id' => 5, 'periodo' => '202607', 'legajo' => 9999, 'cod_nov' => 'HSEX50', 'cantidad' => '1.00', 'int_vac' => 0],
        ]);
        $ahora = now();
        DB::table('motor_novedad_mapeo')->insert([
            ['cod_nov' => 'HSEX50', 'columna_origen' => 'cantidad', 'codigo_tipo_novedad' => 'HE_50', 'unidad' => 'HORAS', 'es_importe' => 0, 'factor' => 1, 'accion' => 'NORMALIZAR', 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['cod_nov' => 'VACAC', 'columna_origen' => 'cantidad', 'codigo_tipo_novedad' => 'FERIADO_CANT', 'unidad' => 'CANTIDAD', 'es_importe' => 0, 'factor' => 1, 'accion' => 'IGNORAR', 'activo' => 1, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function test_normaliza_suma_ignora_y_reporta_sin_mapeo(): void
    {
        $r = app(NormalizarNovedades::class)->ejecutar(Periodo::de('2026-07'));

        self::assertSame(2, $r->creadas, 'dos filas HSEX50 → dos novedades HE_50 (el liquidador las suma)');
        self::assertSame(1, $r->ignoradas);
        self::assertSame(['PRESTA' => 1, 'VACAC' => 1], $r->sinMapeo, 'PRESTA sin mapeo; VACAC:int_vac (columna) sin mapeo');
        self::assertSame([9999 => 1], $r->sinRelacion);

        $rlId = (int) DB::table('motor_relacion_laboral')->value('relacion_laboral_id');
        $nov = app(RepositorioNovedades::class)->porPeriodoYRelaciones(Periodo::de('2026-07'), [$rlId])[$rlId];
        self::assertCount(2, $nov);
        self::assertSame('9.5000', (string) $nov[0]->cantidad);
        self::assertNull($nov[0]->valor);
    }

    public function test_es_idempotente_y_detecta_cambios_y_anulaciones(): void
    {
        $caso = app(NormalizarNovedades::class);
        $caso->ejecutar(Periodo::de('2026-07'));
        $r2 = $caso->ejecutar(Periodo::de('2026-07'));
        self::assertSame([0, 0, 2], [$r2->creadas, $r2->actualizadas, $r2->sinCambio]);

        DB::table('sue028s')->where('id', 1)->update(['cantidad' => '12.00']);
        DB::table('sue028s')->where('id', 2)->delete();
        $r3 = $caso->ejecutar(Periodo::de('2026-07'));
        self::assertSame(1, $r3->actualizadas);
        self::assertSame(1, $r3->anuladas);
        self::assertSame(1, DB::table('motor_novedades')->count());
        self::assertSame('12.0000', DB::table('motor_novedades')->value('cantidad'));
    }
}
