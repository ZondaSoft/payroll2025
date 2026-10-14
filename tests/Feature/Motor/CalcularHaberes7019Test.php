<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\CalcularHaberes;
use App\Motor\Dominio\Formulas\BaseConstruida;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Brick\Math\RoundingMode;
use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Tests\Support\Motor\EscenarioLibro;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/**
 * El legajo 7019 liquidado de punta a punta: desde las tablas del motor hasta el
 * importe del recibo, pasando por los perfiles, el grafo y el DSL.
 *
 * Es el criterio de aceptación del tramo: los mismos importes que el test de
 * dominio, pero resueltos con los repositorios Eloquent, las fórmulas publicadas
 * en motor_concepto_versiones y la configuración cargada como la cargaría el
 * cliente. Si algo falta en la configuración, el motor lo dice — no lo supone.
 */
final class CalcularHaberes7019Test extends TestCase
{
    use UsaEsquemaMotor;

    private const PERIODO = '202607';

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
        EscenarioLibro::sembrar();
        EscenarioLibro::novedades(EscenarioLibro::RL_7019, self::PERIODO, ['HE_50' => '9.5', 'HE_100' => '12']);
    }

    private function correr(int $relacion = EscenarioLibro::RL_7019): EstadoCorrida
    {
        return app(CalcularHaberes::class)->ejecutar($relacion, Periodo::de(self::PERIODO));
    }

    private function importe(EstadoCorrida $e, string $concepto, int $decimales = 4): string
    {
        $r = $e->concepto($concepto);
        self::assertNotNull($r, "no se calculó {$concepto}; calculados: ".implode(', ', array_keys($e->conceptos)));
        self::assertTrue($r->esCalculado(), "{$concepto} quedó {$r->estado} ({$r->codigoError}): {$r->motivo}");

        return $r->importeResultado->toScale($decimales, RoundingMode::HALF_UP)->__toString();
    }

    public function test_los_haberes_del_7019_cierran_con_el_recibo(): void
    {
        $e = $this->correr();

        self::assertSame('1335827.1800', $this->importe($e, 'BASICO'));
        self::assertSame('213732.3488', $this->importe($e, 'ANTIG'));
        self::assertSame('106866.1700', $this->importe($e, 'ASIST'));
        self::assertSame('118020.3310', $this->importe($e, 'HE50'));
        self::assertSame('198771.0839', $this->importe($e, 'HE100'));
    }

    public function test_la_base_de_hora_normal_sale_de_la_composicion_cargada(): void
    {
        $base = $this->correr()->base('BASE_HORA_NORMAL');

        self::assertSame(BaseConstruida::OK, $base->estado, (string) $base->motivo);
        self::assertSame('1656425.6988', (string) $base->importe);
        self::assertEqualsCanonicalizing(['BASICO', 'ASIST', 'ANTIG'], array_keys($base->incluidos));
    }

    public function test_el_valor_hora_usa_el_divisor_del_convenio(): void
    {
        // HMJORN del 371/03 = 200. El 40/89 tiene 192 y por eso es un valor por
        // convenio y no una constante del motor.
        self::assertSame('8282.128494', (string) $this->correr()->concepto('HE50')->valorUnitario);
    }

    public function test_el_basico_toma_la_variante_del_perfil_de_convenio(): void
    {
        $r = $this->correr()->concepto('BASICO');

        self::assertSame('JORNADA_PACTADA', $r->variante);
        self::assertSame(['VAR(BASCAT) = 1335827.180000', 'VAR(HSPACT) = 48', 'VAR(HSJORN) = 48.000000'], $r->traza->resumen());
    }

    public function test_el_perfil_del_trabajador_excluye_el_manejo_de_fondos(): void
    {
        $r = $this->correr()->concepto('MANFON');

        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertSame('El legajo no tiene manejo de fondos.', $r->motivo);
    }

    public function test_los_conceptos_del_otro_convenio_quedan_fuera_con_su_motivo(): void
    {
        $e = $this->correr();

        foreach (['KMREC', 'PERNOCT', 'AD562', 'ADICNR20'] as $concepto) {
            self::assertSame(ResultadoConcepto::NO_APLICA, $e->concepto($concepto)->estado,
                "{$concepto} debería quedar fuera del 371/03");
        }
        self::assertSame('Kilometraje: concepto del CCT 40/89.', $e->concepto('KMREC')->motivo);
    }

    public function test_la_antiguedad_sale_del_modulo_y_del_modelo_del_convenio(): void
    {
        $r = $this->correr()->concepto('ANTIG');

        // 8 años cumplidos × 2 % = 16 % sobre BASE_ANTIGUEDAD
        self::assertSame(['BASE(BASE_ANTIGUEDAD) = 1335827.18', 'MOD(ANTIGUEDAD.PORCENTAJE) = 0.160000'], $r->traza->resumen());
    }

    public function test_sin_novedades_las_horas_extras_no_aplican_y_el_resto_liquida(): void
    {
        \Illuminate\Support\Facades\DB::table('motor_novedades')->delete();
        $e = $this->correr();

        self::assertSame(ResultadoConcepto::NO_APLICA, $e->concepto('HE50')->estado);
        self::assertSame('sin novedad HE_50', $e->concepto('HE50')->motivo);
        self::assertSame('1656425.6988', (string) $e->base('BASE_HORA_NORMAL')->importe);
    }

    public function test_sin_el_tope_de_inasistencias_el_premio_asistencia_bloquea(): void
    {
        // La fórmula de ASIST referencia PARAM(INASIS_MAX), que el libro v5 no
        // declaraba. Sin valor cargado el motor bloquea: no cobrar ni dejar de
        // cobrar el premio por falta de dato.
        $id = \Illuminate\Support\Facades\DB::table('motor_parametros')->where('codigo', 'INASIS_MAX')->value('id_parametro');
        \Illuminate\Support\Facades\DB::table('motor_valores_parametros')->where('id_parametro', $id)->delete();

        $e = $this->correr();

        self::assertSame('PARAM_SIN_VALOR', $e->concepto('ASIST')->codigoError);
        // Y la base que ASIST integra se bloquea con él, en vez de armarse incompleta.
        self::assertSame(BaseConstruida::BLOQUEADA, $e->base('BASE_HORA_NORMAL')->estado);
        self::assertSame('BASE_BLOQUEADA', $e->concepto('HE50')->codigoError);
    }

    public function test_el_simulador_devuelve_la_traza_y_el_orden_del_grafo(): void
    {
        $usuario = \App\Models\User::query()->create([
            'name' => 'Test', 'email' => 'simulador@example.com', 'password' => bcrypt('secret'),
        ]);

        $r = $this->actingAs($usuario)->postJson(route('motor.simulador.haberes'), [
            'relacion_laboral_id' => EscenarioLibro::RL_7019,
            'periodo' => '2026-07',
            'dias_liquidados' => 30,
        ])->assertOk()->json();

        // Las preguntas del gate: qué dio, de dónde salió, de qué depende y en qué orden.
        $he50 = collect($r['conceptos'])->firstWhere('codigo', 'HE50');
        self::assertSame('CALCULADO', $he50['estado']);
        self::assertSame('118020.33103950000', $he50['importe']);
        self::assertSame([
            'NOV(HE_50) = 9.5000',
            'BASE(BASE_HORA_NORMAL) = 1656425.6988',
            'VAR(HMJORN) = 200.000000',
        ], $he50['traza']);

        $base = collect($r['bases'])->firstWhere('codigo', 'BASE_HORA_NORMAL');
        self::assertSame('OK', $base['estado']);
        self::assertEqualsCanonicalizing(['BASICO', 'ASIST', 'ANTIG'], array_keys($base['incluidos']));
        // Cada exclusión viaja con su causa: el recibo puede explicar qué no está y por qué.
        self::assertSame('no aplica', $base['excluidos']['MANFON']);
        self::assertSame('no aplica', $base['excluidos']['AD562']);

        // El orden no está declarado: lo deriva el grafo.
        $orden = array_column($r['orden'], 'codigo');
        self::assertLessThan(array_search('HE50', $orden, true), array_search('BASE_HORA_NORMAL', $orden, true));
        // El subtotal del recibo: 1.973.217,11
        self::assertSame('1973217.11369550000', $r['neto']);
        self::assertSame(0, $r['resumen']['bloqueados']);
    }

    public function test_el_221_del_40_89_usa_la_otra_variante_del_basico(): void
    {
        $e = $this->correr(EscenarioLibro::RL_221);
        $r = $e->concepto('BASICO');

        self::assertSame('LARGA_DISTANCIA', $r->variante);
        self::assertSame('1060010.2900', $this->importe($e, 'BASICO'));
        // H-4: en larga distancia las horas extras no corresponden por perfil, no
        // porque falte la novedad.
        self::assertSame(ResultadoConcepto::NO_APLICA, $e->concepto('HE50')->estado);
        self::assertStringContainsString('H-4', $e->concepto('HE50')->motivo);
    }
}
