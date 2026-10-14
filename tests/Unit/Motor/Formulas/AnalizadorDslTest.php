<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\Analizador;
use App\Motor\Dominio\Formulas\ContextoAnalisis;
use PHPUnit\Framework\TestCase;

/**
 * Álgebra dimensional y validación de referencias — port de los bloques C a H
 * de src/test_dsl_v11.js.
 *
 * Es la parte del contrato que impide que una fórmula "ande" por casualidad:
 * sumar horas con días, pagar un importe que resultó ser un porcentaje, o
 * comparar una magnitud contra un escalar.
 */
final class AnalizadorDslTest extends TestCase
{
    private Analizador $analizador;

    protected function setUp(): void
    {
        $this->analizador = new Analizador;
    }

    private function ctx(?string $concepto = null, array $extra = []): ContextoAnalisis
    {
        return new ContextoAnalisis(
            catalogos: CatalogoDePrueba::crear(),
            conceptoActual: $concepto,
            componente: $extra['componente'] ?? ContextoAnalisis::IMPORTE,
            tieneCantidad: $extra['tieneCantidad'] ?? false,
            tieneValor: $extra['tieneValor'] ?? false,
            unidadCantidad: $extra['unidadCantidad'] ?? null,
            unidadValor: $extra['unidadValor'] ?? null,
        );
    }

    public function test_las_formulas_verificadas_del_libro_resultan_en_pesos(): void
    {
        foreach ([
            'VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)',
            'VAR(BASCAT)',
            'BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)',
            'CONC(BASICO) * PARAM(PORC_ASISTENCIA)',
            'CONC(BASICO) * VALCAT(PLUSPLUR)',
            'BASE(BASE_ACUERDO_5_6_2) * PARAM(PORC_ACUERDO_EMPRESA_SINDICATO_5_6_2)',
        ] as $expresion) {
            $r = $this->analizador->analizar($expresion, $this->ctx('PLUSPLUR'));
            self::assertSame([], $r->codigosDeProblema(), "{$expresion}: ".json_encode($r->problemas, JSON_UNESCAPED_UNICODE));
            self::assertSame('ARS', (string) $r->unidad, $expresion);
        }
    }

    public function test_el_valor_hora_sale_en_pesos_por_hora_y_el_valor_dia_en_pesos_por_dia(): void
    {
        $hora = $this->analizador->analizar('BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', $this->ctx('HE50'));
        self::assertSame('ARS/HORAS', (string) $hora->unidad);

        $dia = $this->analizador->analizar('BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)', $this->ctx('FERTRAB'));
        self::assertSame('ARS/DIAS', (string) $dia->unidad);
    }

    public function test_cantidad_por_valor_unitario_cancela_la_unidad_y_da_pesos(): void
    {
        // HE50: horas × (ARS/hora) × 1.5 = ARS
        $r = $this->analizador->analizar('CANT * VALUNI * 1.5', $this->ctx('HE50', [
            'tieneCantidad' => true, 'tieneValor' => true,
            'unidadCantidad' => 'HORAS', 'unidadValor' => 'ARS/HORA',
        ]));

        self::assertSame([], $r->codigosDeProblema());
        self::assertSame('ARS', (string) $r->unidad);
    }

    public function test_no_se_suman_unidades_distintas(): void
    {
        // días de inasistencia + llegadas tarde no es una operación válida
        $r = $this->analizador->analizar('NOV(INASIS) + NOV(LLEGTAR)', $this->ctx());

        self::assertTrue($r->tiene('DSL_UNIDAD_INCOMPATIBLE'));
    }

    public function test_una_magnitud_no_se_compara_contra_un_escalar(): void
    {
        // el tope se declara en la misma unidad que la magnitud comparada
        $malo = $this->analizador->analizar('NOV(INASIS) > 0', $this->ctx());
        self::assertTrue($malo->tiene('DSL_UNIDAD_INCOMPATIBLE'), 'comparar DIAS contra un escalar');

        $bueno = $this->analizador->analizar('NOV(INASIS) > PARAM(INASIS_MAX)', $this->ctx());
        self::assertSame([], $bueno->codigosDeProblema());
        self::assertSame('BOOLEAN', (string) $bueno->unidad);
    }

    public function test_el_literal_cero_solo_unifica_las_ramas_de_si(): void
    {
        // dentro de SI() el 0 toma la unidad de la otra rama
        $dentro = $this->analizador->analizar(
            'SI( NOV(INASIS) > PARAM(INASIS_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
            $this->ctx('ASIST')
        );
        self::assertSame([], $dentro->codigosDeProblema());
        self::assertSame('ARS', (string) $dentro->unidad);

        // fuera de SI() sigue siendo un escalar: sumarlo a pesos es incompatible
        $fuera = $this->analizador->analizar('CONC(BASICO) + 0', $this->ctx('ASIST'));
        self::assertTrue($fuera->tiene('DSL_UNIDAD_INCOMPATIBLE'));
    }

    public function test_las_dos_ramas_de_si_deben_coincidir_en_unidad(): void
    {
        $r = $this->analizador->analizar('SI( NOV(INASIS) > PARAM(INASIS_MAX), NOV(HE_50), CONC(BASICO) )', $this->ctx());

        self::assertTrue($r->tiene('DSL_UNIDAD_INCOMPATIBLE'));
    }

    public function test_los_operadores_logicos_trabajan_sobre_comparaciones(): void
    {
        $r = $this->analizador->analizar('CONC(BASICO) Y CONC(ANTIG)', $this->ctx());

        self::assertTrue($r->tiene('DSL_TIPO_INCOMPATIBLE'));
    }

    public function test_no_se_opera_aritmeticamente_con_un_valor_logico(): void
    {
        $r = $this->analizador->analizar('(NOV(INASIS) > PARAM(INASIS_MAX)) * 2', $this->ctx());

        self::assertTrue($r->tiene('DSL_TIPO_INCOMPATIBLE'));
    }

    public function test_nov_acepta_un_solo_codigo_porque_f2_elimino_el_atributo(): void
    {
        // La versión anterior del DSL exigía NOV(COD, CANT); F-2/P-6 lo eliminó
        $r = $this->analizador->analizar('NOV(HE_50, CANT)', $this->ctx());

        self::assertTrue($r->tiene('DSL_ARGUMENTOS_INVALIDOS'));
    }

    public function test_param_indexado_no_es_canonico(): void
    {
        $r = $this->analizador->analizar('PARAM(TARIFA_KM, ANTANIO)', $this->ctx());

        self::assertTrue($r->tiene('DSL_PARAM_INDEXADO_NO_CANONICO'));
    }

    public function test_valcat_solo_lee_el_valor_del_propio_concepto(): void
    {
        $ajeno = $this->analizador->analizar('CONC(BASICO) * VALCAT(PLUSPLUR)', $this->ctx('ADTIT'));
        self::assertTrue($ajeno->tiene('DSL_VALCAT_AJENO'));

        $propio = $this->analizador->analizar('CONC(BASICO) * VALCAT(PLUSPLUR)', $this->ctx('PLUSPLUR'));
        self::assertSame([], $propio->codigosDeProblema());
    }

    public function test_un_concepto_no_se_referencia_a_si_mismo(): void
    {
        $r = $this->analizador->analizar('CONC(BASICO) * 2', $this->ctx('BASICO'));

        self::assertTrue($r->tiene('DSL_AUTORREFERENCIA'));
    }

    public function test_r13_un_concepto_no_puede_consumir_la_base_que_integra(): void
    {
        // BASICO integra BASE_HORA_NORMAL: si la consumiera se calcularía sobre sí mismo
        $r = $this->analizador->analizar('BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', $this->ctx('BASICO'));
        self::assertTrue($r->tiene('DSL_CONSUME_E_INTEGRA'));

        // HE50 queda fuera de la base justamente para poder consumirla
        $ok = $this->analizador->analizar('BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', $this->ctx('HE50'));
        self::assertSame([], $ok->codigosDeProblema());
    }

    public function test_las_referencias_inexistentes_se_informan_sin_inventar_unidad(): void
    {
        foreach ([
            'PARAM(NO_EXISTE)', 'NOV(NO_EXISTE)', 'VAR(NOEX)', 'VALCAT(NO_EXISTE)',
            'CONC(NO_EXISTE)', 'BASE(NO_EXISTE)', 'MOD(NO.EXISTE)',
        ] as $expresion) {
            $r = $this->analizador->analizar($expresion, $this->ctx());
            self::assertTrue($r->tiene('DSL_REFERENCIA_INEXISTENTE'), $expresion);
        }
    }

    public function test_la_unidad_sin_declarar_advierte_pero_no_rompe(): void
    {
        // No se puede afirmar incompatibilidad con una unidad que el catálogo no declara
        $r = $this->analizador->analizar('CONC(BASICO) * PARAM(SIN_UNIDAD)', $this->ctx());

        self::assertSame([], $r->codigosDeProblema(), 'no es un error dimensional');
        self::assertSame(['PARAM(SIN_UNIDAD)'], $r->sinUnidad, 'se informa para bloquear al publicar');
        self::assertSame('SIN DECLARAR', (string) $r->unidad);
    }

    public function test_la_novedad_debe_declarar_su_regla_de_agregacion(): void
    {
        $r = $this->analizador->analizar('NOV(SIN_AGREGACION)', $this->ctx());

        self::assertTrue($r->tiene('ERROR_CONFIGURACION'));
    }

    public function test_cant_y_valuni_exigen_que_la_version_declare_su_expresion(): void
    {
        $sinCantidad = $this->analizador->analizar('CANT * VALUNI', $this->ctx('HE50', ['tieneValor' => true]));
        self::assertTrue($sinCantidad->tiene('DSL_CANT_SIN_EXPRESION'));

        $sinValor = $this->analizador->analizar('CANT * VALUNI', $this->ctx('HE50', ['tieneCantidad' => true]));
        self::assertTrue($sinValor->tiene('DSL_VALUNI_SIN_EXPRESION'));

        // VALUNI sólo tiene sentido en la expresión de importe
        $enCantidad = $this->analizador->analizar('VALUNI', $this->ctx('HE50', [
            'componente' => ContextoAnalisis::CANTIDAD, 'tieneValor' => true,
        ]));
        self::assertTrue($enCantidad->tiene('DSL_SINTAXIS_INVALIDA'));
    }

    public function test_el_alias_de_variable_se_advierte_y_se_resuelve_al_canonico(): void
    {
        $r = $this->analizador->analizar('VAR(BASICO_CATEGORIA)', $this->ctx());

        self::assertTrue($r->tiene('DSL_ALIAS_NO_CANONICO'));
        self::assertSame('ARS', (string) $r->unidad, 'resuelve igual a la variable canónica');
        self::assertSame('BASCAT', $r->dependencias[0]['codigo']);
    }

    public function test_registra_las_dependencias_que_generan_arista_en_el_grafo(): void
    {
        $r = $this->analizador->analizar('BASE(BASE_HORA_NORMAL) / VAR(HMJORN) * CONC(BASICO)', $this->ctx('HE50'));

        // PARAM, NOV, VAR y VALCAT no generan arista; CONC, BASE y MOD sí
        self::assertSame([
            ['tipo' => 'BASE', 'codigo' => 'BASE_HORA_NORMAL'],
            ['tipo' => 'CONCEPTO', 'codigo' => 'BASICO'],
        ], array_map(fn (array $d) => ['tipo' => $d['tipo'], 'codigo' => $d['codigo']], $r->aristas()));
    }

    public function test_los_literales_quedan_registrados_para_exigir_justificacion(): void
    {
        $r = $this->analizador->analizar('CANT * VALUNI * 1.5', $this->ctx('HE50', [
            'tieneCantidad' => true, 'tieneValor' => true, 'unidadCantidad' => 'HORAS', 'unidadValor' => 'ARS/HORA',
        ]));

        self::assertSame(['1.5'], array_column($r->literales, 'valor'));
    }
}
