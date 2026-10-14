<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\CalcularHaberes;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Brick\Math\RoundingMode;
use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorReglasLibroDocenteSeeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Tests\Support\Motor\EscenarioLibroDocente;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/**
 * Los casos del libro docente del CCT 371/03 (Operario de Playa, agosto 2026).
 *
 * Cada caso explica una regla, y las reglas son las que el motor no tenía: cómo se
 * expone una ausencia, cuándo el básico se proporciona y cuándo no, y qué pasa con
 * el premio asistencia. El criterio es el del libro v3 y lo decide la configuración
 * — las fórmulas con vigencia 08/2026 conviven con las de 07/2026 sin reemplazarlas.
 *
 * Los importes son los de las hojas P01 a P13 de
 * CCT371_Libro_Docente_Operario_Playa_082026_v3.xlsx (SHA-256 809745ac…), que
 * redondean cada línea a dos decimales.
 */
final class LibroDocente371Test extends TestCase
{
    use UsaEsquemaMotor;

    /** Las cuatro líneas fijas del recibo, iguales en los casos de mes completo. */
    private const FIJOS_MES_COMPLETO = [
        'BASICO' => '1350389.10',
        'ANTIG' => '378108.95',
        'ASIST' => '108031.13',
        'MANFON' => '108031.13',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
        // Las reglas del libro docente son posteriores al libro v5 y el seeder del v5
        // reinserta todas las versiones: hay que reaplicarlas después.
        $this->seed(MotorReglasLibroDocenteSeeder::class);
        EscenarioLibroDocente::sembrar();
    }

    /**
     * @param  array<string, string>  $novedades
     */
    private function correr(array $novedades = [], int $diasLiquidados = 30, ?int $diasEfectivos = null): EstadoCorrida
    {
        if ($novedades !== []) {
            EscenarioLibroDocente::novedades($novedades);
        }

        return app(CalcularHaberes::class)->ejecutar(
            EscenarioLibroDocente::RL,
            Periodo::de(EscenarioLibroDocente::PERIODO),
            $diasLiquidados,
            $diasEfectivos,
        );
    }

    private function importe(EstadoCorrida $e, string $concepto): string
    {
        $r = $e->concepto($concepto);
        self::assertNotNull($r, "no se calculó {$concepto}");
        self::assertTrue($r->esCalculado(), "{$concepto} quedó {$r->estado} ({$r->codigoError}): {$r->motivo}");

        return $r->importeResultado->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /** @param array<string, string> $esperados */
    private function assertLineas(EstadoCorrida $e, array $esperados): void
    {
        foreach ($esperados as $concepto => $importe) {
            self::assertSame($importe, $this->importe($e, $concepto), $concepto);
        }
    }

    public function test_p01_mes_completo(): void
    {
        $e = $this->correr();

        $this->assertLineas($e, self::FIJOS_MES_COMPLETO);
        // La base de la hora normal del testigo: los cuatro fijos ya redondeados.
        self::assertSame('1944560.31', $e->base('BASE_HORA_NORMAL')->importe->toScale(2, RoundingMode::HALF_UP)->__toString());
        // Tolerancia horaria: 9 h × (básico ÷ 200), no remunerativa.
        self::assertSame('60767.51', $this->importe($e, 'TOLHOR'));
        self::assertSame('9.000000', (string) $e->concepto('TOLHOR')->cantidad);
    }

    public function test_p02_ingreso_el_16_proporciona_el_basico_y_los_adicionales(): void
    {
        $e = $this->correr(diasLiquidados: 15);

        // Sólo las altas reales proporcionan. Los adicionales salen del básico ya
        // proporcionado, no del mensual completo.
        $this->assertLineas($e, [
            'BASICO' => '675194.55', 'ANTIG' => '189054.47', 'ASIST' => '54015.56', 'MANFON' => '54015.56',
        ]);
        self::assertSame('30383.75', $this->importe($e, 'TOLHOR'));
    }

    public function test_p03_egreso_el_20(): void
    {
        $e = $this->correr(diasLiquidados: 20);

        $this->assertLineas($e, [
            'BASICO' => '900259.40', 'ANTIG' => '252072.63', 'ASIST' => '72020.75', 'MANFON' => '72020.75',
        ]);
        self::assertSame('40511.67', $this->importe($e, 'TOLHOR'));
    }

    public function test_p04_inasistencia_injustificada(): void
    {
        $e = $this->correr(['INASIS' => '1'], diasEfectivos: 29);

        // El básico NO se achica: el mes va completo y la falta se expone aparte.
        $this->assertLineas($e, [
            'BASICO' => '1350389.10', 'ANTIG' => '378108.95', 'MANFON' => '108031.13',
        ]);
        // Una falta injustificada elimina el premio asistencia (CCT art. 13).
        self::assertSame('0.00', $this->importe($e, 'ASIST'));
        // Haber remunerativo negativo, no un descuento. La base diaria excluye el
        // premio perdido porque su fórmula ya devolvió cero: no se descuenta dos veces.
        self::assertSame('-61217.64', $this->importe($e, 'INASIS'));
        self::assertSame(-1, $e->concepto('INASIS')->factorSigno);
        // La tolerancia sí se prorratea: 9 h × 29/30.
        self::assertSame('58741.93', $this->importe($e, 'TOLHOR'));
        self::assertSame('8.700000', (string) $e->concepto('TOLHOR')->cantidad);
    }

    public function test_p05_suspension_de_tres_dias(): void
    {
        $e = $this->correr(['DIAS_SUSP' => '3'], diasEfectivos: 27);

        $this->assertLineas($e, ['BASICO' => '1350389.10', 'ANTIG' => '378108.95', 'MANFON' => '108031.13']);
        self::assertSame('0.00', $this->importe($e, 'ASIST'));
        self::assertSame('-183652.92', $this->importe($e, 'SUSPEN'));
        self::assertSame('54690.76', $this->importe($e, 'TOLHOR'));
    }

    public function test_p07_licencia_sin_goce_mantiene_el_premio_asistencia(): void
    {
        $e = $this->correr(['DIAS_SIN_GOCE' => '11'], diasEfectivos: 19);

        // A diferencia de la inasistencia y la suspensión, la licencia sin goce no
        // hace perder el premio: el CCT no la enumera.
        $this->assertLineas($e, self::FIJOS_MES_COMPLETO);
        // Y por eso su base diaria es mayor: 1.944.560,31 ÷ 30 = 64.818,677.
        self::assertSame('-713005.45', $this->importe($e, 'SINGOCE'));
        self::assertSame('38486.09', $this->importe($e, 'TOLHOR'));
    }

    public function test_p11_horas_extras_sobre_la_base_de_hora_normal(): void
    {
        $e = $this->correr(['HE_50' => '8', 'HE_100' => '4']);

        $this->assertLineas($e, self::FIJOS_MES_COMPLETO + ['HE50' => '116673.62', 'HE100' => '77782.41']);
        // 1.944.560,31 ÷ 200 = 9.722,80155 la hora normal; × 1,5 y × 2 los recargos.
        self::assertSame('9722.801550', (string) $e->concepto('HE50')->valorUnitario);
    }

    public function test_p12_feriado_trabajado_con_el_divisor_de_jornales(): void
    {
        $e = $this->correr(['FERIADO_CANT' => '1']);

        $this->assertLineas($e, self::FIJOS_MES_COMPLETO + ['FERTRAB' => '77782.41']);
        // El mensual ya tiene la remuneración normal del feriado: se agrega una
        // cantidad igual, calculada con el divisor de jornales (25 en el 371/03).
        self::assertSame('77782.412400', (string) $e->concepto('FERTRAB')->valorUnitario);
    }

    public function test_p13_dia_de_la_actividad_con_recargo_del_cien_por_ciento(): void
    {
        $e = $this->correr(['DIA_ACTIVIDAD' => '1']);

        // Mismo cálculo que el feriado: el recargo del 100 % es pagar una jornada
        // entera además del mensual. El /25 es criterio operativo del libro fuente.
        $this->assertLineas($e, self::FIJOS_MES_COMPLETO + ['DIAACT' => '77782.41']);
    }

    public function test_sin_novedad_de_ausencia_los_conceptos_negativos_no_aplican(): void
    {
        $e = $this->correr();

        foreach (['INASIS', 'SUSPEN', 'SINGOCE', 'DIAACT'] as $concepto) {
            $r = $e->concepto($concepto);
            self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado, $concepto);
            self::assertStringStartsWith('sin novedad', (string) $r->motivo, $concepto);
        }
    }

    public function test_las_ausencias_no_integran_la_base_de_la_hora_normal(): void
    {
        $e = $this->correr(['INASIS' => '1'], diasEfectivos: 29);

        // Si la integraran, la falta se descontaría de su propia base: el concepto
        // que consume una base no puede integrarla (R13).
        self::assertArrayNotHasKey('INASIS', $e->base('BASE_HORA_NORMAL')->incluidos);
        self::assertSame('1836529.18', $e->base('BASE_HORA_NORMAL')->importe->toScale(2, RoundingMode::HALF_UP)->__toString());
    }
}
