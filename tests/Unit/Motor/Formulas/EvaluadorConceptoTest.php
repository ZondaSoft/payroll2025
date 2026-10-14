<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\EvaluadorConcepto;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\Formulas\VersionFormula;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;

/**
 * Un concepto evaluado de punta a punta: la secuencia del §6 y el cierre con
 * redondeo, signo e impacto en el neto.
 *
 * Lo que se verifica acá es sobre todo la diferencia entre NO_APLICA y BLOQUEADO:
 * que falte la novedad de horas extras significa que ese mes no hubo horas extras,
 * y que falte el valor de un parámetro significa que la configuración está
 * incompleta. Lo primero liquida, lo segundo no.
 */
final class EvaluadorConceptoTest extends TestCase
{
    private function evaluar(VersionFormula $v, ResolvedorDePrueba $r, bool $aplica = true, ?string $motivo = null): ResultadoConcepto
    {
        return (new EvaluadorConcepto)->evaluar($v, CatalogoDePrueba::crear(), $r, $aplica, $motivo);
    }

    private function he50(): VersionFormula
    {
        return new VersionFormula('HE50',
            exprCantidad: 'NOV(HE_50)',
            exprValorUnitario: 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
            exprImporte: 'CANT * VALUNI * 1.5',
            unidadCantidad: 'HORAS',
            novedades: ['HE_50' => 'REQUERIDA'],
            justificaciones: ['1.5' => 'Recargo del 50 % — art. 201 LCT'],
        );
    }

    public function test_he50_del_7019_con_sus_nueve_horas_y_media(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba(
            novedades: ['HE_50' => '9.5'],
            variables: ['HMJORN' => '200'],
            bases: ['BASE_HORA_NORMAL' => '1656425.6988'],
        ));

        self::assertTrue($r->esCalculado());
        self::assertSame('9.5', (string) $r->cantidad);
        self::assertSame('8282.128494', (string) $r->valorUnitario);
        self::assertSame('118020.3310', $r->importeResultado->toScale(4, RoundingMode::HALF_UP)->__toString());
    }

    public function test_sin_la_novedad_requerida_el_concepto_no_aplica(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba(
            variables: ['HMJORN' => '200'],
            bases: ['BASE_HORA_NORMAL' => '1656425.6988'],
        ));

        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertSame('sin novedad HE_50', $r->motivo);
        self::assertNull($r->importeResultado);
    }

    public function test_una_base_sin_construir_bloquea_el_concepto(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba(
            novedades: ['HE_50' => '9.5'],
            variables: ['HMJORN' => '200'],
        ));

        self::assertSame(ResultadoConcepto::BLOQUEADO, $r->estado);
        self::assertSame('BASE_BLOQUEADA', $r->codigoError);
    }

    public function test_un_parametro_sin_valor_bloquea_y_no_vale_cero(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ASIST', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)'),
            new ResolvedorDePrueba(conceptos: ['BASICO' => '1335827.18'])
        );

        self::assertSame(ResultadoConcepto::BLOQUEADO, $r->estado);
        self::assertSame('PARAM_SIN_VALOR', $r->codigoError);
    }

    public function test_el_perfil_puede_dejar_el_concepto_fuera(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba, aplica: false,
            motivo: 'en larga distancia las horas extras no corresponden');

        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertSame('en larga distancia las horas extras no corresponden', $r->motivo);
    }

    public function test_la_condicion_que_no_se_cumple_deja_el_concepto_fuera(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ASIST',
                exprCondicion: 'NOV(INASIS) <= PARAM(INASIS_MAX)',
                exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)',
                novedades: ['INASIS' => 'OPCIONAL'],
            ),
            new ResolvedorDePrueba(
                parametros: ['INASIS_MAX' => '1', 'PORC_ASISTENCIA' => '0.08'],
                novedades: ['INASIS' => '3'],
                conceptos: ['BASICO' => '1335827.18'],
            )
        );

        self::assertSame(ResultadoConcepto::NO_APLICA, $r->estado);
        self::assertSame('la condición de la fórmula no se cumple', $r->motivo);
    }

    public function test_una_novedad_opcional_ausente_vale_cero_tipado(): void
    {
        // ASIST con la condición del v5: sin novedades de inasistencia, cobra.
        $r = $this->evaluar(
            new VersionFormula('ASIST',
                exprImporte: 'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
                reglaRedondeo: 'DECIMAL',
                novedades: ['INASIS' => 'OPCIONAL', 'LLEGTAR' => 'OPCIONAL'],
            ),
            new ResolvedorDePrueba(
                parametros: ['INASIS_MAX' => '1', 'LLEGTAR_MAX' => '2', 'PORC_ASISTENCIA' => '0.08'],
                conceptos: ['BASICO' => '1335827.18'],
            )
        );

        self::assertTrue($r->esCalculado());
        self::assertSame('106866.17', (string) $r->importeResultado);
    }

    public function test_el_redondeo_decimal_de_la_version_entra_en_la_base(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ASIST', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)', reglaRedondeo: 'DECIMAL'),
            new ResolvedorDePrueba(parametros: ['PORC_ASISTENCIA' => '0.08'], conceptos: ['BASICO' => '1335827.18'])
        );

        // El bruto conserva todos los decimales del producto; lo que se integra es el redondeado.
        self::assertSame('106866.1744', (string) $r->importeBruto);
        self::assertSame('106866.17', (string) $r->importeResultado);
    }

    public function test_el_redondeo_entero(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ASIST', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)', reglaRedondeo: 'ENTERO'),
            new ResolvedorDePrueba(parametros: ['PORC_ASISTENCIA' => '0.08'], conceptos: ['BASICO' => '1335827.18'])
        );

        self::assertSame('106866', (string) $r->importeResultado);
    }

    public function test_el_signo_debito_invierte_el_importe(): void
    {
        $r = $this->evaluar(
            new VersionFormula('APJUB', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)', signo: 'DEBITO'),
            new ResolvedorDePrueba(parametros: ['PORC_ASISTENCIA' => '0.08'], conceptos: ['BASICO' => '1335827.18'])
        );

        self::assertSame(-1, $r->factorSigno);
        self::assertSame('-106866.1744', (string) $r->importeResultado);
        self::assertTrue($r->impactaNeto);
    }

    public function test_el_signo_neutro_cuenta_pero_no_impacta_el_neto(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ACTAFUT', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)', signo: 'NEUTRO'),
            new ResolvedorDePrueba(parametros: ['PORC_ASISTENCIA' => '0.08'], conceptos: ['BASICO' => '1335827.18'])
        );

        self::assertSame(1, $r->factorSigno);
        self::assertFalse($r->impactaNeto);
        self::assertSame('106866.1744', (string) $r->importeResultado);
    }

    public function test_el_metodo_modulo_toma_el_importe_del_responsable(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ANTIG', metodo: VersionFormula::MODULO, codigoModulo: 'ANTIGUEDAD.IMPORTE'),
            new ResolvedorDePrueba(modulos: ['ANTIGUEDAD.IMPORTE' => '213732.3488'])
        );

        self::assertTrue($r->esCalculado());
        self::assertSame('213732.3488', (string) $r->importeResultado);
        self::assertSame(['MOD(ANTIGUEDAD.IMPORTE) = 213732.3488'], $r->traza->resumen());
    }

    public function test_un_modulo_sin_resultado_bloquea(): void
    {
        $r = $this->evaluar(
            new VersionFormula('ANTIG', metodo: VersionFormula::MODULO, codigoModulo: 'PROMEDIO.VARIABLE_6M'),
            new ResolvedorDePrueba
        );

        self::assertSame(ResultadoConcepto::BLOQUEADO, $r->estado);
        self::assertSame('MOD_SIN_RESULTADO', $r->codigoError);
    }

    public function test_una_division_por_cero_bloquea_con_su_codigo(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba(
            novedades: ['HE_50' => '9.5'],
            variables: ['HMJORN' => '0'],
            bases: ['BASE_HORA_NORMAL' => '1656425.6988'],
        ));

        self::assertSame(ResultadoConcepto::BLOQUEADO, $r->estado);
        self::assertSame('DIVISION_CERO', $r->codigoError);
    }

    public function test_la_traza_registra_lo_que_entro_al_calculo(): void
    {
        $r = $this->evaluar($this->he50(), new ResolvedorDePrueba(
            novedades: ['HE_50' => '9.5'],
            variables: ['HMJORN' => '200'],
            bases: ['BASE_HORA_NORMAL' => '1656425.6988'],
        ));

        self::assertSame([
            'NOV(HE_50) = 9.5',
            'BASE(BASE_HORA_NORMAL) = 1656425.6988',
            'VAR(HMJORN) = 200',
        ], $r->traza->resumen());
    }

    public function test_la_variante_viaja_en_el_resultado(): void
    {
        $r = $this->evaluar(
            new VersionFormula('BASICO', 'JORNADA_PACTADA', exprImporte: 'VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)'),
            new ResolvedorDePrueba(variables: ['BASCAT' => '1335827.18', 'HSPACT' => '48', 'HSJORN' => '48'])
        );

        self::assertSame('JORNADA_PACTADA', $r->variante);
        self::assertSame('1335827.18', $r->importeResultado->toScale(2)->__toString());
    }
}
