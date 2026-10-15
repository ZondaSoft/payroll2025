<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\SimularLiquidacion;
use App\Motor\Aplicacion\DTO\SolicitudSimulacion;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\EscenarioLibro;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/**
 * Prueba de aceptación del T-221 de punta a punta en el producto: las fórmulas
 * publicadas calculan los haberes y, sobre ese mismo resultado, se componen las
 * bases ARCA y se liquidan aportes y contribuciones.
 *
 * Esperados del instructivo de Oscar (V2 post B-3, §13, §18, §19 y §30), que son
 * los mismos que verifica sim/test_motor_contributivo.js: condición ARCA 5,
 * encuadramiento B, regímenes general + transporte de cargas (RC-002), sin
 * detracción de la Ley 27.430. En este caso el motor no liquida obra social, FSR
 * ni ART: no hay alícuotas que inventar.
 */
final class SimularLiquidacion221Test extends TestCase
{
    use UsaEsquemaMotor;

    private const PERIODO = '202607';

    private const REM_ORDINARIA = '2686619.032110';

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
        EscenarioLibro::sembrar();
        EscenarioLibro::novedades(EscenarioLibro::RL_221, self::PERIODO, EscenarioLibro::NOVEDADES_221);
        $this->encuadreContributivoDel221();
    }

    /** Condición ARCA 5, encuadramiento B de la empresa y los regímenes GENERAL + TRANSP_CARGAS. */
    private function encuadreContributivoDel221(): void
    {
        $ahora = now()->toDateTimeString();
        DB::table('motor_condiciones_arca')->insertOrIgnore(['codigo_condicion' => '5', 'descripcion' => 'Condición 5', 'activo' => true]);
        DB::table('motor_modalidades_arca')->insertOrIgnore(['codigo_modalidad' => '8', 'descripcion' => 'Modalidad 8', 'activo' => true]);
        DB::table('motor_relacion_laboral_arca')->insert([
            'relacion_laboral_id' => EscenarioLibro::RL_221, 'codigo_condicion' => '5', 'codigo_modalidad' => '8',
            'vigencia_desde' => '2009-07-01', 'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        DB::table('motor_empresa_encuadramiento')->insert([
            'empresa_id' => EscenarioLibro::EMPRESA, 'encuadramiento' => 'B', 'vigencia_desde' => '2026-01-01',
            'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        foreach (['GENERAL', 'TRANSP_CARGAS'] as $codigo) {
            DB::table('motor_relacion_laboral_regimen')->insert([
                'relacion_laboral_id' => EscenarioLibro::RL_221,
                'id_regimen' => DB::table('motor_regimenes_contributivos')->where('codigo', $codigo)->value('id_regimen'),
                'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private function simular(): array
    {
        return app(SimularLiquidacion::class)->ejecutar(new SolicitudSimulacion(
            EscenarioLibro::RL_221, Periodo::de(self::PERIODO), TipoLiquidacion::Normal, Entorno::Prueba,
        ))->aArray();
    }

    public function test_los_haberes_salen_de_las_formulas_publicadas(): void
    {
        $r = $this->simular();
        $h = array_column($r['haberes'], 'importe_exacto', 'codigo');

        self::assertSame('1060010.290000', $h['BASICO']);
        self::assertSame('208105.703100', $h['KMREC']);
        self::assertSame('374748.911146', $h['ANTIG']);
        self::assertSame('107464.761284', $h['FERTRAB']);
        self::assertSame('236175.627000', $h['ADICNR20']);
        self::assertSame('FORMULA', $r['haberes'][0]['detalle']['origen']);
        self::assertContains('HE50', array_column($r['no_ejecutados'], 'codigo'), 'H-4: en larga distancia HE50 no aplica');
    }

    public function test_bases_arca_del_221(): void
    {
        $r = $this->simular();
        $bases = $r['bases_arca'];

        foreach (['REM_1', 'REM_2', 'REM_3', 'REM_4', 'REM_5', 'REM_8'] as $rem) {
            self::assertSame('CALCULADA', $bases[$rem]['estado'], "{$rem}: {$bases[$rem]['motivo_bloqueo']}");
            self::assertSame(self::REM_ORDINARIA, $bases[$rem]['importe_exacto'], $rem);
        }
        // ADICNR20 no integra Seguridad Social ni Obra Social, pero sí ART (§13).
        self::assertSame('2922794.659110', $bases['REM_9']['importe_exacto']);
        self::assertSame('N', collect($bases['REM_1']['detalle'])->firstWhere('codigo_concepto', 'ADICNR20')['incidencia']);
        self::assertSame('S', collect($bases['REM_9']['detalle'])->firstWhere('codigo_concepto', 'ADICNR20')['incidencia']);
        self::assertSame('N', collect($bases['REM_9']['detalle'])->firstWhere('codigo_concepto', 'VIATKM')['incidencia']);
        self::assertArrayNotHasKey('REM_10', $bases, 'sin detracción informada el simulador no deriva REM_10');
    }

    public function test_aportes_del_221(): void
    {
        $r = $this->simular();
        $aportes = array_column($r['aportes'], 'importe', 'subsistema');

        self::assertSame(['SIPA' => '295528.09', 'INSSJP' => '80598.57'], $aportes);
    }

    public function test_contribuciones_b_mas_rc_002_del_221(): void
    {
        $r = $this->simular();
        $porLinea = [];
        foreach ($r['contribuciones'] as $c) {
            $porLinea[$c['subsistema'].'|'.$c['regla']] = $c['importe'];
        }

        self::assertSame('289348.87', $porLinea['SIPA|BASE']);
        self::assertSame('42717.24', $porLinea['INSSJP|BASE']);
        self::assertSame('126271.09', $porLinea['AAFF|BASE']);
        self::assertSame('25254.22', $porLinea['FNE|BASE']);
        self::assertSame('53732.38', $porLinea['SIPA|ADICIONAL'], 'RC-002 transporte de cargas: 2 % sobre REM_2');
        self::assertSame('537323.80', number_format(array_sum(array_map('floatval', $porLinea)), 2, '.', ''));
    }

    public function test_sin_divjor_el_feriado_bloquea_las_bases_que_integra_y_no_hay_seguridad_social(): void
    {
        // PE-2 (auditoría pre-entrega, 2026-09-26): FERTRAB queda BLOQUEADO por VAR_SIN_VALOR,
        // es S en las 13 aplicaciones, y ninguna base ordinaria puede afirmarse.
        DB::table('motor_valores_variable')->where('codigo', 'DIVJOR')->where('id_convenio', EscenarioLibro::CONVENIO_4089)->delete();

        $r = $this->simular();

        self::assertSame('BLOQUEADO', $r['estado']);
        foreach (['REM_1', 'REM_2', 'REM_3', 'REM_4', 'REM_5', 'REM_8', 'REM_9'] as $rem) {
            self::assertSame('BLOQUEADA', $r['bases_arca'][$rem]['estado'], $rem);
            self::assertNull($r['bases_arca'][$rem]['importe_aplicado'], "{$rem} bloqueada no vale cero");
        }
        self::assertSame([], $r['aportes']);
        self::assertSame([], $r['contribuciones']);
    }

    public function test_renatre_sin_regimen_rural_no_aplica_y_no_bloquea(): void
    {
        $r = $this->simular();

        self::assertContains('RENATRE|APORTE', array_column($r['no_ejecutados'], 'codigo'));
        self::assertNotContains('RENATRE', array_column($r['aportes'], 'subsistema'));
        self::assertNotContains('ALICUOTA_AUSENTE', array_column($r['bloqueos'], 'codigo'));
    }

    public function test_renatre_con_regimen_rural_y_sin_alicuota_de_aporte_bloquea(): void
    {
        DB::table('motor_relacion_laboral_regimen')->insert([
            'relacion_laboral_id' => EscenarioLibro::RL_221,
            'id_regimen' => DB::table('motor_regimenes_contributivos')->where('codigo', 'RURAL_RENATRE')->value('id_regimen'),
            'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $r = $this->simular();

        $bloqueos = array_filter($r['bloqueos'], fn ($b) => $b['etapa'] === 'aportes');
        self::assertContains('ALICUOTA_AUSENTE', array_column($bloqueos, 'codigo'), 'trabajador rural sin alícuota de aporte RENATRE: bloquea');
    }
}
