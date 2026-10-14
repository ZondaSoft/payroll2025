<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Formulas\ValidadorVersion;
use App\Motor\Dominio\Formulas\VersionFormula;
use PHPUnit\Framework\TestCase;

/**
 * La puerta de publicación de una versión: qué pasa y qué no.
 *
 * El criterio del contrato es que publicar sea costoso y editar no: en EDICION
 * los avisos de migración y la unidad que el catálogo no declara son advertencias,
 * y en PUBLICACION bloquean. Un error dimensional bloquea en los dos modos.
 */
final class ValidadorVersionTest extends TestCase
{
    private function validar(VersionFormula $v, string $modo = ValidadorVersion::PUBLICACION): \App\Motor\Dominio\Formulas\ResultadoValidacion
    {
        return (new ValidadorVersion)->validar($v, CatalogoDePrueba::crear(), $modo);
    }

    /** HE50 completo del libro: cantidad en horas, valor por hora con recargo, importe CANT × VALUNI. */
    private function he50(array $cambios = []): VersionFormula
    {
        return new VersionFormula(...array_merge([
            'codigoConcepto' => 'HE50',
            'exprCantidad' => 'NOV(HE_50)',
            'exprValorUnitario' => 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
            'exprImporte' => 'CANT * VALUNI * 1.5',
            'unidadCantidad' => 'HORAS',
            'novedades' => ['HE_50' => 'REQUERIDA'],
            'justificaciones' => ['1.5' => 'Recargo del 50 % — art. 201 LCT'],
        ], $cambios));
    }

    public function test_la_version_del_libro_publica(): void
    {
        $r = $this->validar($this->he50());

        self::assertTrue($r->publica(), 'errores: '.json_encode($r->errores, JSON_UNESCAPED_UNICODE));
        self::assertSame('ARS', (string) $r->unidadImporte);
    }

    public function test_metodo_modulo_exige_responsable(): void
    {
        $r = $this->validar(new VersionFormula('ANTIG', metodo: VersionFormula::MODULO));

        self::assertFalse($r->publica());
        self::assertSame(['DSL_MODULO_SIN_RESPONSABLE'], $r->codigos());
    }

    public function test_metodo_modulo_con_responsable_no_necesita_formula(): void
    {
        $r = $this->validar(new VersionFormula('ANTIG', metodo: VersionFormula::MODULO, codigoModulo: 'ANTIGUEDAD.PORCENTAJE'));

        self::assertTrue($r->publica());
    }

    public function test_el_importe_es_obligatorio(): void
    {
        $r = $this->validar(new VersionFormula('BASICO'));

        self::assertContains('DSL_IMPORTE_OBLIGATORIO', $r->codigos());
    }

    public function test_la_condicion_debe_devolver_un_valor_logico(): void
    {
        $r = $this->validar(new VersionFormula('ASIST',
            exprCondicion: 'NOV(INASIS)',
            exprImporte: 'VAR(BASCAT) * PARAM(PORC_ASISTENCIA)',
            novedades: ['INASIS' => 'OPCIONAL'],
        ));

        self::assertContains('DSL_TIPO_INCOMPATIBLE', $r->codigos());
    }

    public function test_la_condicion_comparada_publica(): void
    {
        $r = $this->validar(new VersionFormula('ASIST',
            exprCondicion: 'NOV(INASIS) <= PARAM(INASIS_MAX)',
            exprImporte: 'VAR(BASCAT) * PARAM(PORC_ASISTENCIA)',
            novedades: ['INASIS' => 'OPCIONAL'],
        ));

        self::assertTrue($r->publica(), 'errores: '.json_encode($r->errores, JSON_UNESCAPED_UNICODE));
    }

    public function test_la_unidad_declarada_de_la_cantidad_debe_coincidir(): void
    {
        $r = $this->validar($this->he50(['unidadCantidad' => 'DIAS']));

        self::assertContains('DSL_UNIDAD_INCOMPATIBLE', $r->codigos());
    }

    public function test_el_importe_debe_devolver_ars(): void
    {
        $r = $this->validar(new VersionFormula('HE50',
            exprCantidad: 'NOV(HE_50)',
            exprImporte: 'CANT',
            unidadCantidad: 'HORAS',
            novedades: ['HE_50' => 'REQUERIDA'],
        ));

        self::assertContains('DSL_RESULTADO_NO_ARS', $r->codigos());
    }

    public function test_un_catalogo_sin_unidad_no_puede_verificarse_al_publicar(): void
    {
        $r = $this->validar(new VersionFormula('ADTIT', exprImporte: 'PARAM(SIN_UNIDAD)'));

        self::assertContains('CFG_UNIDAD_SIN_DECLARAR', $r->codigos());
        self::assertStringContainsString('PARAM(SIN_UNIDAD)', $r->errores[0]['mensaje']);
    }

    public function test_en_edicion_la_unidad_sin_declarar_es_advertencia(): void
    {
        $r = $this->validar(new VersionFormula('ADTIT', exprImporte: 'PARAM(SIN_UNIDAD)'), ValidadorVersion::EDICION);

        self::assertTrue($r->publica());
        self::assertTrue($r->tiene('CFG_UNIDAD_SIN_DECLARAR'));
    }

    public function test_en_edicion_un_error_dimensional_sigue_bloqueando(): void
    {
        $r = $this->validar(new VersionFormula('HE50', exprImporte: 'VAR(HMJORN)'), ValidadorVersion::EDICION);

        self::assertFalse($r->publica());
        self::assertContains('DSL_RESULTADO_NO_ARS', $r->codigos());
    }

    public function test_un_literal_necesita_justificacion_para_publicarse(): void
    {
        $r = $this->validar($this->he50(['justificaciones' => []]));

        self::assertContains('DSL_LITERAL_SIN_JUSTIFICAR', $r->codigos());
    }

    /**
     * El cero que unifica las ramas de SI() no necesita justificación (§15), y es
     * la única excepción dimensional del contrato: dentro de SI() el 0 toma la
     * dimensión de la otra rama; fuera, comparar DIAS con 0 es un error.
     */
    public function test_el_cero_de_si_no_necesita_justificacion(): void
    {
        $r = $this->validar(new VersionFormula('ASIST',
            exprImporte: 'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
            reglaRedondeo: 'DECIMAL',
            novedades: ['INASIS' => 'OPCIONAL', 'LLEGTAR' => 'OPCIONAL'],
        ));

        self::assertTrue($r->publica(), 'errores: '.json_encode($r->errores, JSON_UNESCAPED_UNICODE));
        self::assertSame('ARS', (string) $r->unidadImporte);
    }

    public function test_comparar_una_novedad_en_dias_contra_cero_es_error_dimensional(): void
    {
        $r = $this->validar(new VersionFormula('ASIST',
            exprCondicion: 'NOV(INASIS) > 0',
            exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)',
            novedades: ['INASIS' => 'OPCIONAL'],
        ));

        self::assertContains('DSL_UNIDAD_INCOMPATIBLE', $r->codigos());
        self::assertStringContainsString('comparar DIAS con ESCALAR', $r->errores[0]['mensaje']);
    }

    /** Las 26 fórmulas del v5 pasan por acá: FERTRAB con su divisor por convenio. */
    public function test_fertrab_del_v5_publica_sin_condicion(): void
    {
        $r = $this->validar(new VersionFormula('FERTRAB',
            exprCantidad: 'NOV(FERIADO_CANT)',
            exprValorUnitario: 'BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)',
            exprImporte: 'CANT * VALUNI',
            unidadCantidad: 'DIAS',
            novedades: ['FERIADO_CANT' => 'REQUERIDA'],
        ));

        self::assertTrue($r->publica(), 'errores: '.json_encode($r->errores, JSON_UNESCAPED_UNICODE));
    }

    public function test_una_novedad_usada_sin_declarar_no_publica(): void
    {
        $r = $this->validar($this->he50(['novedades' => []]));

        self::assertContains('DSL_NOVEDAD_NO_DECLARADA', $r->codigos());
    }

    public function test_una_novedad_declarada_que_nadie_usa_no_publica(): void
    {
        $r = $this->validar($this->he50(['novedades' => ['HE_50' => 'REQUERIDA', 'HE_100' => 'OPCIONAL']]));

        self::assertContains('DSL_NOVEDAD_DECLARADA_NO_USADA', $r->codigos());
    }

    public function test_la_obligatoriedad_solo_admite_requerida_u_opcional(): void
    {
        $r = $this->validar($this->he50(['novedades' => ['HE_50' => 'SI']]));

        self::assertContains('ERROR_CONFIGURACION', $r->codigos());
    }

    public function test_declara_las_aristas_de_conceptos_bases_y_modulos(): void
    {
        $r = $this->validar(new VersionFormula('ANTIG',
            exprValorUnitario: 'MOD(ANTIGUEDAD.PORCENTAJE)',
            exprImporte: 'BASE(BASE_ANTIGUEDAD) * VALUNI + CONC(BASICO) * 0',
            justificaciones: [],
        ));

        $aristas = array_map(fn (array $d) => $d['tipo'].':'.$d['codigo'], $r->aristas());
        self::assertContains('MODULO:ANTIGUEDAD.PORCENTAJE', $aristas);
        self::assertContains('BASE:BASE_ANTIGUEDAD', $aristas);
        self::assertContains('CONCEPTO:BASICO', $aristas);
    }

    public function test_un_catalogo_vacio_reporta_la_referencia_inexistente(): void
    {
        $r = (new ValidadorVersion)->validar(
            new VersionFormula('BASICO', exprImporte: 'VAR(BASCAT)'),
            new CatalogoDsl
        );

        self::assertFalse($r->publica());
        self::assertContains('DSL_REFERENCIA_INEXISTENTE', $r->codigos());
    }
}
