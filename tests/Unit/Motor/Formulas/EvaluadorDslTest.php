<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\BloqueoDsl;
use App\Motor\Dominio\Formulas\Evaluador;
use App\Motor\Dominio\Formulas\Parser;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;

/**
 * El evaluador ejecutando las fórmulas verificadas del libro.
 *
 * Los importes esperados son los de los recibos de 07/2026 que Oscar cerró al
 * centavo en sim/config_7019.json y sim/config_221.json; acá se comprueba que el
 * port los reproduce desde la misma expresión DSL.
 */
final class EvaluadorDslTest extends TestCase
{
    private function evaluar(string $expresion, ResolvedorDePrueba $resolvedor, array $reservadas = []): BigDecimal
    {
        $ev = new Evaluador(CatalogoDePrueba::crear(), $resolvedor);

        return $ev->evaluar((new Parser)->parsear($expresion), $reservadas);
    }

    public function test_basico_del_7019_variante_jornada_pactada(): void
    {
        // BASCAT 1.335.827,18 × HSPACT 48 / HSJORN 48 = 1.335.827,18
        $r = $this->evaluar('VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)', new ResolvedorDePrueba(
            variables: ['BASCAT' => '1335827.18', 'HSPACT' => '48', 'HSJORN' => '48']
        ));

        self::assertSame('1335827.18', $r->toScale(2)->__toString());
    }

    public function test_basico_del_221_variante_larga_distancia(): void
    {
        // En larga distancia no existe HSPACT: el básico es el de escala, sin prorrateo
        $r = $this->evaluar('VAR(BASCAT)', new ResolvedorDePrueba(variables: ['BASCAT' => '1060010.29']));

        self::assertSame('1060010.29', $r->toScale(2)->__toString());
    }

    public function test_antiguedad_del_7019_sobre_su_base(): void
    {
        // BASE_ANTIGUEDAD 1.335.827,18 × 16 % = 213.732,3488
        $r = $this->evaluar('BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)', new ResolvedorDePrueba(
            bases: ['BASE_ANTIGUEDAD' => '1335827.18'],
            modulos: ['ANTIGUEDAD.PORCENTAJE' => '0.16'],
        ));

        self::assertSame('213732.3488', $r->toScale(4)->__toString());
    }

    public function test_asistencia_del_7019_con_su_redondeo_propio(): void
    {
        // 1.335.827,18 × 8 % = 106.866,1744 → el concepto lo redondea a 106.866,17
        $r = $this->evaluar('CONC(BASICO) * PARAM(PORC_ASISTENCIA)', new ResolvedorDePrueba(
            parametros: ['PORC_ASISTENCIA' => '0.08'],
            conceptos: ['BASICO' => '1335827.18'],
        ));

        self::assertSame('106866.1744', $r->toScale(4)->__toString());
        self::assertSame('106866.17', $r->toScale(2, \Brick\Math\RoundingMode::HALF_UP)->__toString());
    }

    public function test_horas_extras_al_50_del_7019(): void
    {
        // hora normal = BASE_HORA_NORMAL 1.656.425,6988 / 200 = 8.282,128494
        // HE50 = 9,5 h × 8.282,128494 × 1,5 = 118.020,3310
        $resolvedor = new ResolvedorDePrueba(
            novedades: ['HE_50' => '9.5'],
            variables: ['HMJORN' => '200'],
            bases: ['BASE_HORA_NORMAL' => '1656425.6988'],
        );
        $cantidad = $this->evaluar('NOV(HE_50)', $resolvedor);
        $valorHora = $this->evaluar('BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', $resolvedor);
        $importe = $this->evaluar('CANT * VALUNI * 1.5', $resolvedor, ['CANT' => $cantidad, 'VALUNI' => $valorHora]);

        self::assertSame('8282.128494', $valorHora->__toString());
        self::assertSame('118020.3310', $importe->toScale(4, \Brick\Math\RoundingMode::HALF_UP)->__toString());
    }

    public function test_feriado_trabajado_del_221_por_dia(): void
    {
        // 1 día × (BASE_HORA_NORMAL 2.579.154,270826 / DIVJOR 24) = 107.464,7613
        $resolvedor = new ResolvedorDePrueba(
            novedades: ['FERIADO_CANT' => '1'],
            variables: ['DIVJOR' => '24'],
            bases: ['BASE_HORA_NORMAL' => '2579154.270826'],
        );
        $valorDia = $this->evaluar('BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)', $resolvedor);
        $importe = $this->evaluar('CANT * VALUNI', $resolvedor, [
            'CANT' => $this->evaluar('NOV(FERIADO_CANT)', $resolvedor), 'VALUNI' => $valorDia,
        ]);

        self::assertSame('107464.7613', $importe->toScale(4, \Brick\Math\RoundingMode::HALF_UP)->__toString());
    }

    public function test_kilometros_recorridos_del_221(): void
    {
        // 2.454 km × 84,80265 = 208.105,7031
        $resolvedor = new ResolvedorDePrueba(parametros: ['TARIFA_KM' => '84.80265'], novedades: ['KM_REM' => '2454']);
        $importe = $this->evaluar('CANT * VALUNI', $resolvedor, [
            'CANT' => $this->evaluar('NOV(KM_REM)', $resolvedor),
            'VALUNI' => $this->evaluar('PARAM(TARIFA_KM)', $resolvedor),
        ]);

        self::assertSame('208105.7031', $importe->toScale(4)->__toString());
    }

    public function test_la_regla_de_asistencia_pierde_el_adicional_con_una_inasistencia(): void
    {
        $formula = 'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )';
        $comun = ['PORC_ASISTENCIA' => '0.08', 'INASIS_MAX' => '0', 'LLEGTAR_MAX' => '2'];

        // sin inasistencias ni llegadas tarde: cobra
        $cobra = $this->evaluar($formula, new ResolvedorDePrueba(parametros: $comun, conceptos: ['BASICO' => '1335827.18']));
        self::assertSame('106866.1744', $cobra->toScale(4)->__toString());

        // un día de inasistencia injustificada: lo pierde
        $pierde = $this->evaluar($formula, new ResolvedorDePrueba(
            parametros: $comun, novedades: ['INASIS' => '1'], conceptos: ['BASICO' => '1335827.18']));
        self::assertSame('0', $pierde->__toString());

        // dos llegadas tarde se toleran, la tercera lo pierde
        $dos = $this->evaluar($formula, new ResolvedorDePrueba(
            parametros: $comun, novedades: ['LLEGTAR' => '2'], conceptos: ['BASICO' => '1335827.18']));
        self::assertSame('106866.1744', $dos->toScale(4)->__toString());

        $tres = $this->evaluar($formula, new ResolvedorDePrueba(
            parametros: $comun, novedades: ['LLEGTAR' => '3'], conceptos: ['BASICO' => '1335827.18']));
        self::assertSame('0', $tres->__toString());
    }

    public function test_una_novedad_ausente_vale_cero_tipado_no_bloquea(): void
    {
        // La obligatoriedad vive en la versión: acá, ausente, es cero
        $r = $this->evaluar('NOV(HE_50)', new ResolvedorDePrueba);

        self::assertSame('0', $r->__toString());
    }

    public function test_un_dato_que_falta_bloquea_y_no_se_convierte_en_cero(): void
    {
        foreach ([
            ['VAR(BASCAT)', 'VAR_SIN_VALOR'],
            ['PARAM(TARIFA_KM)', 'PARAM_SIN_VALOR'],
            ['VALCAT(PLUSPLUR)', 'VALCAT_SIN_VALOR'],
            ['CONC(BASICO)', 'CONC_BLOQUEADO'],
            ['BASE(BASE_HORA_NORMAL)', 'BASE_BLOQUEADA'],
            ['MOD(ANTIGUEDAD.PORCENTAJE)', 'MOD_SIN_RESULTADO'],
        ] as [$expresion, $codigo]) {
            try {
                $this->evaluar($expresion, new ResolvedorDePrueba);
                self::fail("{$expresion} debía bloquear con {$codigo}");
            } catch (BloqueoDsl $e) {
                self::assertSame($codigo, $e->codigo, $expresion);
            }
        }
    }

    public function test_la_division_por_cero_bloquea(): void
    {
        $this->expectException(BloqueoDsl::class);
        $this->expectExceptionMessage('División por cero');

        // HSJORN = 0 en la variante que divide
        $this->evaluar('VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)', new ResolvedorDePrueba(
            variables: ['BASCAT' => '1000', 'HSPACT' => '48', 'HSJORN' => '0']
        ));
    }

    public function test_min_max_abs_y_redondear(): void
    {
        $r = new ResolvedorDePrueba(conceptos: ['BASICO' => '1000.5678', 'ANTIG' => '500']);

        self::assertSame('500', $this->evaluar('MIN(CONC(BASICO), CONC(ANTIG))', $r)->__toString());
        self::assertSame('1000.5678', $this->evaluar('MAX(CONC(BASICO), CONC(ANTIG))', $r)->__toString());
        self::assertSame('1000.57', $this->evaluar('REDONDEAR(CONC(BASICO), 2)', $r)->__toString());
        self::assertSame('500', $this->evaluar('ABS(-CONC(ANTIG))', $r)->__toString());
    }

    public function test_la_traza_explica_de_donde_salio_cada_valor(): void
    {
        $ev = new Evaluador(CatalogoDePrueba::crear(), new ResolvedorDePrueba(
            parametros: ['PORC_ASISTENCIA' => '0.08'], conceptos: ['BASICO' => '1335827.18']
        ));
        $ev->evaluar((new Parser)->parsear('CONC(BASICO) * PARAM(PORC_ASISTENCIA)'));

        self::assertSame(['CONC(BASICO) = 1335827.18', 'PARAM(PORC_ASISTENCIA) = 0.08'], $ev->traza()->resumen());
    }
}
