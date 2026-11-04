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
        // El seed normativo del Excel no trae reglas de aplicabilidad (se cargan por pantalla):
        // sin una regla coincidente ningún concepto se liquida. Se abre el ámbito GENERAL para el
        // recibo mínimo de cualquier trabajador (básico + antigüedad + aportes y contribuciones);
        // los conceptos por novedad (kilómetros, pernoctada, horas extras…) NO se abren porque no
        // corresponden a este legajo de estación de servicio. Va ANTES de publicar las versiones,
        // porque el snapshot de cada versión incluye su aplicabilidad.
        $ahoraSetup = now();
        DB::table('motor_concepto_aplicabilidad')->insert(
            DB::table('motor_conceptos')->where('activo', 1)
                ->whereIn('estrategia_calculo', ['BASICO', 'ANTIGUEDAD', 'MODULO_SISTEMA'])->pluck('codigo_concepto')
                ->map(fn (string $c) => ['codigo_concepto' => $c, 'tipo_ambito' => 'GENERAL', 'accion' => 'INCLUIR',
                    'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahoraSetup, 'updated_at' => $ahoraSetup])
                ->all()
        );
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
        // Sin perfiles cargados ningún concepto puede afirmarse (F-3: la ausencia de regla
        // no es NO_APLICA), y sin encuadramiento no hay contribuciones.
        self::assertContains('PERFIL_SIN_REGLA', $codigos, 'Bloqueos: '.implode(', ', $codigos));
        self::assertContains('ENCUADRAMIENTO_AUSENTE', $codigos);
        self::assertGreaterThanOrEqual(2, count($codigos));
    }

    /**
     * Antes este caso liquidaba con las estrategias tipadas aunque ningún perfil
     * dijera qué le corresponde al trabajador. Con un solo camino de cálculo, el
     * básico cargado no alcanza: sin perfil cada concepto bloquea, las bases que
     * integran quedan BLOQUEADAS y no se inventan aportes ni contribuciones.
     */
    public function test_sin_perfiles_no_liquida_aunque_haya_basico_y_encuadramiento(): void
    {
        $ahora = now();
        DB::table('motor_basicos')->insert(['id_categoria' => 300, 'codigo_categoria' => '0106', 'vigencia_desde' => '2026-07-01', 'importe_basico' => '1312961.90', 'moneda' => 'ARS', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);
        DB::table('motor_empresa_encuadramiento')->insert(['empresa_id' => 6, 'encuadramiento' => 'B', 'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'SIN_CONFIRMAR', 'created_at' => $ahora, 'updated_at' => $ahora]);

        $r = $this->simular(Entorno::Prueba);

        self::assertSame('BLOQUEADO', $r['estado']);
        self::assertSame([], $r['haberes'], 'ningún haber sin una regla de perfil que lo alcance');
        self::assertSame('BLOQUEADA', $r['bases_arca']['REM_1']['estado']);
        self::assertNull($r['bases_arca']['REM_1']['importe_aplicado']);
        self::assertSame([], $r['aportes']);
        self::assertSame([], $r['contribuciones']);
        self::assertSame('B', $r['cabecera']['encuadramiento']);
    }
}
