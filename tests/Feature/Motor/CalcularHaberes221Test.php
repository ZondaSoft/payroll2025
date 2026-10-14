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
 * El legajo 221 del CCT 40/89 (chofer de larga distancia), 12 líneas y 4 bases.
 *
 * Es el mismo motor que liquidó el 7019 sin una sola línea de código distinta: lo
 * único que cambia son los datos. Eso es lo que este test verifica — que la
 * variante del básico, los divisores del convenio y las cuatro bases salgan de la
 * configuración y no de una rama en el código.
 *
 * Importes de sim/config_221.json (Guía Maestra cap. 11-bis Caso A).
 */
final class CalcularHaberes221Test extends TestCase
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
        EscenarioLibro::novedades(EscenarioLibro::RL_221, self::PERIODO, EscenarioLibro::NOVEDADES_221);
    }

    private function correr(): EstadoCorrida
    {
        return app(CalcularHaberes::class)->ejecutar(EscenarioLibro::RL_221, Periodo::de(self::PERIODO));
    }

    private function importe(EstadoCorrida $e, string $concepto, int $decimales = 4): string
    {
        $r = $e->concepto($concepto);
        self::assertNotNull($r, "no se calculó {$concepto}");
        self::assertTrue($r->esCalculado(), "{$concepto} quedó {$r->estado} ({$r->codigoError}): {$r->motivo}");

        return $r->importeResultado->toScale($decimales, RoundingMode::HALF_UP)->__toString();
    }

    public function test_las_doce_lineas_del_recibo_del_221(): void
    {
        $e = $this->correr();

        self::assertSame('1060010.2900', $this->importe($e, 'BASICO'));
        self::assertSame('208105.7031', $this->importe($e, 'KMREC'));
        self::assertSame('213872.2833', $this->importe($e, 'KMSABDOM'));
        self::assertSame('212002.0580', $this->importe($e, 'AD562'));
        self::assertSame('265002.6000', $this->importe($e, 'CTRLCD'));
        self::assertSame('90013.5900', $this->importe($e, 'PERMFRB'));
        self::assertSame('155398.8353', $this->importe($e, 'ACDSIND562'));
        self::assertSame('374748.9111', $this->importe($e, 'ANTIG'));
        self::assertSame('107464.7613', $this->importe($e, 'FERTRAB'));
        self::assertSame('1009151.5350', $this->importe($e, 'VIATKM'));
        self::assertSame('171726.6000', $this->importe($e, 'PERMFRA'));
        self::assertSame('236175.6270', $this->importe($e, 'ADICNR20'));
    }

    public function test_las_cuatro_bases(): void
    {
        $e = $this->correr();

        foreach ([
            'BASE_ACUERDO_5_6_2' => '776994.1764',
            'BASE_ANTIGUEDAD' => '2204405.3597',
            'BASE_HORA_NORMAL' => '2579154.2708',
            'BASE_NO_REMUNERATIVA' => '1180878.1350',
        ] as $codigo => $esperado) {
            $base = $e->base($codigo);
            self::assertSame(BaseConstruida::OK, $base->estado, "{$codigo}: {$base->motivo}");
            self::assertSame($esperado, $base->importe->toScale(4, RoundingMode::HALF_UP)->__toString(), $codigo);
        }
    }

    public function test_los_dos_subtotales(): void
    {
        $e = $this->correr();

        $remunerativos = ['BASICO', 'KMREC', 'KMSABDOM', 'AD562', 'CTRLCD', 'PERMFRB', 'ACDSIND562', 'ANTIG', 'FERTRAB'];
        $noRemunerativos = ['VIATKM', 'PERMFRA', 'ADICNR20'];

        self::assertSame('2686619.0321', $e->sumaDe($remunerativos)->toScale(4, RoundingMode::HALF_UP)->__toString());
        self::assertSame('1417053.7620', $e->sumaDe($noRemunerativos)->toScale(4, RoundingMode::HALF_UP)->__toString());
    }

    public function test_la_antiguedad_del_221_usa_el_modelo_lineal_del_40_89(): void
    {
        // 17 años × 1 % = 17 %. El 371/03 usa escala (2 % y 3 %); el modelo lo
        // asigna el convenio, no el motor.
        self::assertSame(
            ['BASE(BASE_ANTIGUEDAD) = 2204405.35968', 'MOD(ANTIGUEDAD.PORCENTAJE) = 0.170000'],
            $this->correr()->concepto('ANTIG')->traza->resumen()
        );
    }

    public function test_el_feriado_trabajado_usa_el_divisor_de_jornales_del_convenio(): void
    {
        $r = $this->correr()->concepto('FERTRAB');

        // DIVJOR del 40/89 = 24 jornales; el 371/03 tiene 25. No se deriva de
        // HMJORN/HDJORN (P-10-b).
        self::assertSame('1.0000', (string) $r->cantidad);
        self::assertSame(
            ['NOV(FERIADO_CANT) = 1.0000', 'BASE(BASE_HORA_NORMAL) = 2579154.2708256', 'VAR(DIVJOR) = 24.000000'],
            $r->traza->resumen()
        );
    }

    public function test_en_larga_distancia_las_horas_extras_no_corresponden_por_perfil(): void
    {
        $e = $this->correr();

        foreach (['HE50', 'HE100'] as $concepto) {
            $r = $e->concepto($concepto);
            self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
            // H-4: la exclusión es estructural del perfil. No es que falte la novedad:
            // KMREC y KMSABDOM son la compensación que las sustituye.
            self::assertStringContainsString('H-4', $r->motivo);
        }
        self::assertTrue($e->concepto('KMREC')->esCalculado());
    }

    public function test_la_pernoctada_corresponde_al_perfil_pero_falta_la_novedad(): void
    {
        $r = $this->correr()->concepto('PERNOCT');

        // La distinción importa: el concepto está habilitado para la categoría, este
        // mes no hubo pernoctadas. Si el perfil lo excluyera, nunca se podría pagar.
        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertSame('sin novedad PERNOCTADA', $r->motivo);
    }

    public function test_los_conceptos_del_371_quedan_fuera_con_su_motivo(): void
    {
        $e = $this->correr();

        foreach (['ASIST', 'MANFON', 'TOLHOR', 'ADTIT'] as $concepto) {
            self::assertSame(ResultadoConcepto::NO_APLICA, $e->concepto($concepto)->estado, $concepto);
            self::assertStringContainsString('371/03', $e->concepto($concepto)->motivo, $concepto);
        }
    }

    public function test_el_plus_por_pluralidad_es_de_las_categorias_de_taller(): void
    {
        $r = $this->correr()->concepto('PLUSPLUR');

        // VALCAT(PLUSPLUR) sólo tiene valor en 203/204/205. El perfil lo excluye
        // antes de que el DSL intente leer un VALCAT que no existe.
        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertStringContainsString('taller', $r->motivo);
    }

    public function test_el_orden_de_calculo_encadena_las_cuatro_bases(): void
    {
        $orden = $this->correr()->orden;
        $cadena = ['B:BASE_ACUERDO_5_6_2', 'C:ACDSIND562', 'B:BASE_ANTIGUEDAD', 'C:ANTIG', 'B:BASE_HORA_NORMAL', 'C:FERTRAB'];

        // El acuerdo sindical depende de su base, la antigüedad de la suya (que
        // incluye el acuerdo) y el feriado de la hora normal (que incluye la
        // antigüedad). Tres niveles de dependencia resueltos sin declarar ninguno.
        self::assertSame($cadena, array_values(array_filter($orden, fn (string $n) => in_array($n, $cadena, true))));
    }
}
