<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Formulas\GrafoCalculo;
use App\Motor\Dominio\Formulas\VersionFormula;
use PHPUnit\Framework\TestCase;

/**
 * El orden de cálculo sale del grafo, no de una columna orden_calculo.
 *
 * Es el cambio de fondo frente al liquidador actual, que tiene las etapas fijas:
 * acá agregar un concepto que consuma una base nueva reordena la corrida sin
 * tocar código.
 */
final class GrafoCalculoTest extends TestCase
{
    /** @param list<VersionFormula> $versiones */
    private function construir(array $versiones, ?CatalogoDsl $catalogos = null): array
    {
        return (new GrafoCalculo)->construir($versiones, $catalogos ?? CatalogoDePrueba::crear());
    }

    private function posicion(array $orden, string $nodo): int
    {
        $i = array_search($nodo, $orden, true);
        self::assertNotFalse($i, "el nodo {$nodo} no está en el orden: ".implode(', ', $orden));

        return $i;
    }

    /** Las versiones del v5 que forman la cadena del 7019. */
    private function versionesDel7019(): array
    {
        return [
            new VersionFormula('BASICO', 'JORNADA_PACTADA', exprImporte: 'VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)'),
            new VersionFormula('ANTIG', exprImporte: 'BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)'),
            new VersionFormula('ASIST', exprImporte: 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)'),
            new VersionFormula('MANFON', exprImporte: 'CONC(BASICO) * PARAM(PORC_MANEJO_FONDOS)'),
            new VersionFormula('AD562', exprImporte: 'CONC(BASICO) * PARAM(PORC_ADIC_BASICO_5_6_2)'),
            new VersionFormula('HE50',
                exprCantidad: 'NOV(HE_50)',
                exprValorUnitario: 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
                exprImporte: 'CANT * VALUNI * 1.5',
                unidadCantidad: 'HORAS',
                novedades: ['HE_50' => 'REQUERIDA'],
            ),
        ];
    }

    public function test_el_basico_se_calcula_antes_de_lo_que_lo_referencia(): void
    {
        $g = $this->construir($this->versionesDel7019());

        self::assertFalse($g['ciclo']);
        $orden = $g['orden'];
        self::assertLessThan($this->posicion($orden, 'C:ASIST'), $this->posicion($orden, 'C:BASICO'));
        self::assertLessThan($this->posicion($orden, 'C:AD562'), $this->posicion($orden, 'C:BASICO'));
    }

    public function test_una_base_se_arma_despues_de_sus_integrantes_y_antes_de_quien_la_consume(): void
    {
        $orden = $this->construir($this->versionesDel7019())['orden'];

        foreach (['C:BASICO', 'C:ANTIG', 'C:ASIST', 'C:MANFON', 'C:AD562'] as $integrante) {
            self::assertLessThan($this->posicion($orden, 'B:BASE_HORA_NORMAL'), $this->posicion($orden, $integrante),
                "{$integrante} debería calcularse antes de armar BASE_HORA_NORMAL");
        }
        self::assertLessThan($this->posicion($orden, 'C:HE50'), $this->posicion($orden, 'B:BASE_HORA_NORMAL'));
    }

    public function test_la_antiguedad_depende_de_su_propia_base(): void
    {
        $orden = $this->construir($this->versionesDel7019())['orden'];

        self::assertLessThan($this->posicion($orden, 'C:ANTIG'), $this->posicion($orden, 'B:BASE_ANTIGUEDAD'));
        // Y BASE_ANTIGUEDAD integra sólo el básico: la antigüedad no se calcula sobre sí misma.
        self::assertLessThan($this->posicion($orden, 'B:BASE_ANTIGUEDAD'), $this->posicion($orden, 'C:BASICO'));
    }

    public function test_un_modulo_arrastra_sus_dependencias_al_grafo(): void
    {
        $catalogos = new CatalogoDsl(
            conceptos: ['BASICO' => [], 'ANTIG' => []],
            variables: ['BASCAT' => ['unidad' => 'ARS']],
            bases: ['BASE_ANTIGUEDAD' => ['integrantes' => ['BASICO']]],
            modulos: ['PROMEDIO.VARIABLE' => ['unidad' => 'ARS', 'dependencias' => ['CONC(BASICO)', 'BASE(BASE_ANTIGUEDAD)']]],
        );
        $g = $this->construir([
            new VersionFormula('BASICO', exprImporte: 'VAR(BASCAT)'),
            new VersionFormula('ANTIG', exprImporte: 'MOD(PROMEDIO.VARIABLE)'),
        ], $catalogos);

        self::assertSame(['C:ANTIG'], $g['aristas']['B:BASE_ANTIGUEDAD']);
        self::assertContains('C:ANTIG', $g['aristas']['C:BASICO']);
        self::assertFalse($g['ciclo']);
    }

    public function test_detecta_un_ciclo_entre_dos_conceptos(): void
    {
        $catalogos = new CatalogoDsl(conceptos: ['A' => [], 'B' => []]);
        $g = $this->construir([
            new VersionFormula('A', exprImporte: 'CONC(B)'),
            new VersionFormula('B', exprImporte: 'CONC(A)'),
        ], $catalogos);

        self::assertTrue($g['ciclo']);
        self::assertEqualsCanonicalizing(['C:A', 'C:B'], $g['nodosEnCiclo']);
    }

    public function test_detecta_el_ciclo_de_un_concepto_que_integra_la_base_que_consume(): void
    {
        $catalogos = new CatalogoDsl(
            conceptos: ['BASICO' => [], 'ANTIG' => []],
            bases: ['BASE_MAL' => ['integrantes' => ['BASICO', 'ANTIG']]],
        );
        $g = $this->construir([new VersionFormula('ANTIG', exprImporte: 'BASE(BASE_MAL)')], $catalogos);

        self::assertTrue($g['ciclo']);
        self::assertContains('C:ANTIG', $g['nodosEnCiclo']);
        self::assertContains('B:BASE_MAL', $g['nodosEnCiclo']);
    }

    public function test_el_orden_es_estable_entre_corridas(): void
    {
        $versiones = $this->versionesDel7019();
        $primero = $this->construir($versiones)['orden'];
        $segundo = $this->construir(array_reverse($versiones))['orden'];

        self::assertSame($primero, $segundo, 'el orden no debe depender de cómo lleguen las versiones');
    }

    public function test_param_nov_var_y_valcat_no_generan_arista(): void
    {
        $g = $this->construir([
            new VersionFormula('KMREC',
                exprCantidad: 'NOV(KM_REM)',
                exprValorUnitario: 'PARAM(TARIFA_KM)',
                exprImporte: 'CANT * VALUNI',
                unidadCantidad: 'KM',
                novedades: ['KM_REM' => 'REQUERIDA'],
            ),
        ], new CatalogoDsl(
            conceptos: ['KMREC' => []],
            parametros: ['TARIFA_KM' => ['unidad' => 'ARS/KM']],
            novedades: ['KM_REM' => ['unidad' => 'KM', 'regla_agregacion' => 'SUMA']],
        ));

        self::assertSame(['C:KMREC'], $g['orden']);
        self::assertSame([], $g['aristas']);
    }
}
