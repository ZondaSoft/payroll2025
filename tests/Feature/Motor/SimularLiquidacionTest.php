<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\SimularLiquidacion;
use App\Motor\Aplicacion\CasosDeUso\SincronizarRelacionesLaborales;
use App\Motor\Aplicacion\DTO\SolicitudSimulacion;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/** Recorrido end-to-end sobre sqlite: seed 07 + versiones + legajo sincronizado + básico → simular. */
final class SimularLiquidacionTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        DB::table('sue086s')->insert(['id' => 6, 'codigo' => '05', 'detalle' => 'BLEXA']);
        DB::table('sue007s')->insert(['id' => 9, 'codigo' => '3', 'detalle' => 'Estaciones de servicio']);
        DB::table('sue006s')->insert(['id' => 300, 'codigo' => '0106', 'detalle' => 'Operario', 'cod_conve' => '3']);
        DB::table('sue001s')->insert(['id' => 15579, 'codigo' => 7019, 'detalle' => 'AGUIRRE', 'nombres' => 'ENRIQUE', 'alta' => '2018-06-11', 'cod_categ' => '0106', 'convenio' => '3', 'grupo_emp' => '05', 'sicoss_condi' => 1, 'sicoss_modal' => 8]);
        app(SincronizarRelacionesLaborales::class)->ejecutar('05', Periodo::de('2026-07'));
    }

    private function simular(Entorno $entorno): array
    {
        $rlId = (int) DB::table('motor_relacion_laboral')->value('relacion_laboral_id');

        return app(SimularLiquidacion::class)->ejecutar(new SolicitudSimulacion($rlId, Periodo::de('2026-07'), TipoLiquidacion::Normal, $entorno))->aArray();
    }

    public function test_en_produccion_bloquea_con_motivos_acumulados(): void
    {
        $r = $this->simular(Entorno::Produccion);

        self::assertSame('BLOQUEADO', $r['estado']);
        $codigos = array_unique(array_column($r['bloqueos'], 'codigo'));
        self::assertContains('BASICO_SIN_IMPORTE', $codigos, 'no hay básico cargado para la categoría real 0106');
        self::assertContains('ENCUADRAMIENTO_AUSENTE', $codigos);
        self::assertGreaterThanOrEqual(2, count($codigos));
    }

    public function test_en_prueba_con_basico_y_encuadramiento_liquida(): void
    {
        $ahora = now();
        DB::table('motor_basicos')->insert(['id_categoria' => 300, 'codigo_categoria' => '0106', 'vigencia_desde' => '2026-07-01', 'importe_basico' => '1312961.90', 'moneda' => 'ARS', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);
        DB::table('motor_empresa_encuadramiento')->insert(['empresa_id' => 6, 'encuadramiento' => 'B', 'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);
        // el convenio real 9 (código 3) necesita modelo de antigüedad para ANTIG
        DB::table('motor_convenio_modelo_antiguedad')->insert(['id_convenio' => 9, 'id_modelo_antiguedad' => 1, 'vigencia_desde' => '2026-07-01', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);
        // parámetros del convenio 371/03 para convenio real 9
        foreach (['PORC_TITULO' => '0.05', 'PORC_ASISTENCIA' => '0.08', 'PORC_MANEJO_FONDOS' => '0.08'] as $cod => $val) {
            $idp = DB::table('motor_parametros')->where('codigo', $cod)->value('id_parametro');
            DB::table('motor_valores_parametros')->insert(['id_parametro' => $idp, 'alcance' => 'CONVENIO', 'id_convenio' => 9, 'valor' => $val, 'vigencia_desde' => '2026-07-01', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);
        }

        $r = $this->simular(Entorno::Prueba);

        self::assertNotSame('BLOQUEADO', $r['estado'], json_encode($r['bloqueos'], JSON_UNESCAPED_UNICODE));
        $h = array_column($r['haberes'], 'importe', 'codigo');
        self::assertSame('1312961.90', $h['BASICO']);
        self::assertSame('210073.90', $h['ANTIG'], '8 años → 16 %');
        self::assertArrayHasKey('REM_1', $r['bases_arca']);
        self::assertNotEmpty($r['aportes']);
        self::assertSame('B', $r['cabecera']['encuadramiento']);
        self::assertContains('SIN_CONFIRMAR', array_column($r['avisos'], 'codigo'));
    }
}
