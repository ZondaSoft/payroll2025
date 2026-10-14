<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\BaseConstruida;
use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Formulas\CorridaFormulas;
use App\Motor\Dominio\Formulas\DecisionPerfil;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Formulas\ResultadoConcepto;
use App\Motor\Dominio\Formulas\VersionFormula;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;

/**
 * Una corrida completa con las fórmulas del v5: el legajo 7019 del CCT 371/03,
 * liquidado en el orden que deriva el grafo.
 *
 * Los importes son los que Oscar cerró contra el recibo en sim/config_7019.json.
 * El que fija el criterio es BASE_HORA_NORMAL = 1.656.425,6988: sale de sumar el
 * ASIST **ya redondeado** a dos decimales y de excluir MANFON, que este legajo no
 * cobra. Con el ASIST sin redondear daría 1.656.425,7032 y las horas extras
 * cerrarían mal.
 */
final class CorridaFormulasTest extends TestCase
{
    /** Las versiones del v5 que intervienen, tal como están en motor_concepto_versiones. */
    private function versiones(): array
    {
        return [
            new VersionFormula('BASICO', 'JORNADA_PACTADA', exprImporte: 'VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)'),
            new VersionFormula('BASICO', 'LARGA_DISTANCIA', exprImporte: 'VAR(BASCAT)'),
            new VersionFormula('ANTIG', exprImporte: 'BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)'),
            new VersionFormula('ASIST',
                exprImporte: 'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
                reglaRedondeo: 'DECIMAL',
                novedades: ['INASIS' => 'OPCIONAL', 'LLEGTAR' => 'OPCIONAL'],
            ),
            new VersionFormula('MANFON', exprImporte: 'CONC(BASICO) * PARAM(PORC_MANEJO_FONDOS)'),
            new VersionFormula('AD562', exprImporte: 'CONC(BASICO) * PARAM(PORC_ADIC_BASICO_5_6_2)'),
            new VersionFormula('HE50',
                exprCantidad: 'NOV(HE_50)', exprValorUnitario: 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
                exprImporte: 'CANT * VALUNI * 1.5', unidadCantidad: 'HORAS',
                novedades: ['HE_50' => 'REQUERIDA'], justificaciones: ['1.5' => 'Recargo 50 % — art. 201 LCT'],
            ),
            new VersionFormula('HE100',
                exprCantidad: 'NOV(HE_100)', exprValorUnitario: 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
                exprImporte: 'CANT * VALUNI * 2', unidadCantidad: 'HORAS',
                novedades: ['HE_100' => 'REQUERIDA'], justificaciones: ['2' => 'Recargo 100 % — art. 201 LCT'],
            ),
        ];
    }

    /** El perfil del 371/03 para este legajo: jornada pactada, sin manejo de fondos ni acuerdo 5/6/2. */
    private function perfil7019(): array
    {
        return [
            'BASICO' => DecisionPerfil::aplica('JORNADA_PACTADA', 'CONVENIO'),
            'ANTIG' => DecisionPerfil::aplica(nivel: 'CONVENIO'),
            'ASIST' => DecisionPerfil::aplica(nivel: 'CONVENIO'),
            'HE50' => DecisionPerfil::aplica(nivel: 'CONVENIO'),
            'HE100' => DecisionPerfil::aplica(nivel: 'CONVENIO'),
            'MANFON' => DecisionPerfil::noAplica('el legajo no tiene manejo de fondos', 'TRABAJADOR'),
            'AD562' => DecisionPerfil::noAplica('el acuerdo 5/6/2 es del CCT 40/89', 'CONVENIO'),
        ];
    }

    private function datos7019(): ResolvedorDePrueba
    {
        return new ResolvedorDePrueba(
            parametros: ['PORC_ASISTENCIA' => '0.08', 'INASIS_MAX' => '1', 'LLEGTAR_MAX' => '2'],
            novedades: ['HE_50' => '9.5', 'HE_100' => '12'],
            variables: ['BASCAT' => '1335827.18', 'HSPACT' => '48', 'HSJORN' => '48', 'HMJORN' => '200', 'DIVJOR' => '25'],
            modulos: ['ANTIGUEDAD.PORCENTAJE' => '0.16'],
        );
    }

    private function correr7019(): EstadoCorrida
    {
        return (new CorridaFormulas)->ejecutar(
            $this->versiones(), CatalogoDePrueba::crear(), $this->datos7019(), $this->perfil7019()
        );
    }

    private function importe(EstadoCorrida $e, string $concepto, int $decimales = 4): string
    {
        $r = $e->concepto($concepto);
        self::assertNotNull($r, "no se calculó {$concepto}");
        self::assertTrue($r->esCalculado(), "{$concepto} quedó {$r->estado}: {$r->motivo}");

        return $r->importeResultado->toScale($decimales, RoundingMode::HALF_UP)->__toString();
    }

    public function test_los_haberes_del_7019_cierran_con_el_recibo(): void
    {
        $e = $this->correr7019();

        self::assertSame('1335827.1800', $this->importe($e, 'BASICO'));
        self::assertSame('213732.3488', $this->importe($e, 'ANTIG'));
        self::assertSame('106866.1700', $this->importe($e, 'ASIST'));
        self::assertSame('118020.3310', $this->importe($e, 'HE50'));
        self::assertSame('198771.0839', $this->importe($e, 'HE100'));
    }

    public function test_la_base_de_hora_normal_excluye_lo_que_no_aplica(): void
    {
        $e = $this->correr7019();
        $base = $e->base('BASE_HORA_NORMAL');

        self::assertSame(BaseConstruida::OK, $base->estado);
        self::assertSame('1656425.6988', (string) $base->importe);
        self::assertSame(['BASICO', 'ANTIG', 'ASIST'], array_keys($base->incluidos));
        self::assertSame(['MANFON' => 'no aplica', 'AD562' => 'no aplica'], $base->excluidos);
    }

    public function test_el_subtotal_remunerativo(): void
    {
        $e = $this->correr7019();

        self::assertSame('1973217.11', $e->neto()->toScale(2, RoundingMode::HALF_UP)->__toString());
    }

    public function test_la_hora_normal_es_la_base_sobre_el_divisor_del_convenio(): void
    {
        $e = $this->correr7019();

        // 1.656.425,6988 / 200 = 8.282,128494 — el valor hora del 371/03
        self::assertSame('8282.128494', (string) $e->concepto('HE50')->valorUnitario);
        self::assertSame('9.5', (string) $e->concepto('HE50')->cantidad);
    }

    public function test_el_orden_de_calculo_lo_resolvio_el_grafo(): void
    {
        $e = $this->correr7019();
        // ASIST y ANTIG son independientes entre sí: a igual grado desempata el orden
        // alfabético, que es lo que hace reproducible la secuencia.
        $cadena = ['C:BASICO', 'B:BASE_ANTIGUEDAD', 'C:ASIST', 'C:ANTIG', 'B:BASE_HORA_NORMAL', 'C:HE50'];

        // No se declara ningún orden_calculo: el básico sale primero porque nadie
        // depende de él, y las horas extras últimas porque dependen de la base que
        // depende del resto.
        self::assertSame($cadena, array_values(array_filter($e->orden, fn (string $n) => in_array($n, $cadena, true))));
    }

    public function test_lo_que_no_aplica_queda_registrado_con_su_motivo(): void
    {
        $e = $this->correr7019();

        $noAplica = $e->porEstado(ResultadoConcepto::NO_APLICA);
        self::assertSame(['AD562', 'MANFON'], array_keys($noAplica));
        self::assertSame('el legajo no tiene manejo de fondos', $noAplica['MANFON']->motivo);
    }

    public function test_sin_novedad_de_horas_extras_el_legajo_liquida_igual(): void
    {
        $e = (new CorridaFormulas)->ejecutar($this->versiones(), CatalogoDePrueba::crear(), new ResolvedorDePrueba(
            parametros: ['PORC_ASISTENCIA' => '0.08', 'INASIS_MAX' => '1', 'LLEGTAR_MAX' => '2'],
            variables: ['BASCAT' => '1335827.18', 'HSPACT' => '48', 'HSJORN' => '48', 'HMJORN' => '200'],
            modulos: ['ANTIGUEDAD.PORCENTAJE' => '0.16'],
        ), $this->perfil7019());

        self::assertSame(ResultadoConcepto::NO_APLICA, $e->concepto('HE50')->estado);
        self::assertSame('1656425.6988', (string) $e->base('BASE_HORA_NORMAL')->importe);
        self::assertSame('1656425.70', $e->neto()->toScale(2, RoundingMode::HALF_UP)->__toString());
    }

    public function test_un_integrante_bloqueado_bloquea_la_base_y_lo_que_la_consume(): void
    {
        // Sin el porcentaje de asistencia, ASIST no puede resolverse: la base no se
        // arma «con lo que haya» y las horas extras quedan bloqueadas por arrastre.
        $e = (new CorridaFormulas)->ejecutar($this->versiones(), CatalogoDePrueba::crear(), new ResolvedorDePrueba(
            parametros: ['INASIS_MAX' => '1', 'LLEGTAR_MAX' => '2'],
            novedades: ['HE_50' => '9.5', 'HE_100' => '12'],
            variables: ['BASCAT' => '1335827.18', 'HSPACT' => '48', 'HSJORN' => '48', 'HMJORN' => '200'],
            modulos: ['ANTIGUEDAD.PORCENTAJE' => '0.16'],
        ), $this->perfil7019());

        self::assertSame('PARAM_SIN_VALOR', $e->concepto('ASIST')->codigoError);
        self::assertSame(BaseConstruida::BLOQUEADA, $e->base('BASE_HORA_NORMAL')->estado);
        self::assertStringContainsString('ASIST está bloqueado', $e->base('BASE_HORA_NORMAL')->motivo);
        self::assertSame('BASE_BLOQUEADA', $e->concepto('HE50')->codigoError);
    }

    public function test_el_perfil_que_no_elige_variante_es_un_error_de_configuracion(): void
    {
        $decisiones = $this->perfil7019();
        $decisiones['BASICO'] = DecisionPerfil::aplica(nivel: 'CONVENIO');

        $e = (new CorridaFormulas)->ejecutar($this->versiones(), CatalogoDePrueba::crear(), $this->datos7019(), $decisiones);

        self::assertSame('ERROR_CONFIGURACION', $e->concepto('BASICO')->codigoError);
        self::assertStringContainsString('JORNADA_PACTADA, LARGA_DISTANCIA', $e->concepto('BASICO')->motivo);
    }

    public function test_el_perfil_que_pide_una_variante_inexistente_tambien(): void
    {
        $decisiones = $this->perfil7019();
        $decisiones['BASICO'] = DecisionPerfil::aplica('MEDIA_JORNADA', 'CONVENIO');

        $e = (new CorridaFormulas)->ejecutar($this->versiones(), CatalogoDePrueba::crear(), $this->datos7019(), $decisiones);

        self::assertSame('ERROR_CONFIGURACION', $e->concepto('BASICO')->codigoError);
        self::assertStringContainsString('MEDIA_JORNADA', $e->concepto('BASICO')->motivo);
    }

    public function test_un_concepto_que_aplica_sin_version_queda_no_modelado(): void
    {
        $catalogos = new CatalogoDsl(conceptos: ['INAVAC' => []], variables: ['BASCAT' => ['unidad' => 'ARS']]);
        $e = (new CorridaFormulas)->ejecutar(
            [new VersionFormula('BASICO', exprImporte: 'VAR(BASCAT)')],
            $catalogos,
            new ResolvedorDePrueba(variables: ['BASCAT' => '1000']),
            ['INAVAC' => DecisionPerfil::aplica(nivel: 'CONVENIO')]
        );

        // El perfil lo pide y no hay fórmula publicada: queda asentado como pendiente
        // de modelar, no omitido en silencio como hace el liquidador por estrategias.
        self::assertSame(ResultadoConcepto::NO_MODELADO, $e->concepto('INAVAC')->estado);
        self::assertSame('PENDIENTE_VERSION', $e->concepto('INAVAC')->codigoError);
    }

    public function test_un_ciclo_bloquea_a_sus_participantes_y_deja_liquidar_el_resto(): void
    {
        $catalogos = new CatalogoDsl(
            conceptos: ['BASICO' => [], 'A' => [], 'B' => []],
            variables: ['BASCAT' => ['unidad' => 'ARS']],
        );
        $e = (new CorridaFormulas)->ejecutar([
            new VersionFormula('BASICO', exprImporte: 'VAR(BASCAT)'),
            new VersionFormula('A', exprImporte: 'CONC(B)'),
            new VersionFormula('B', exprImporte: 'CONC(A)'),
        ], $catalogos, new ResolvedorDePrueba(variables: ['BASCAT' => '1000']));

        self::assertSame('DSL_CICLO_DEPENDENCIAS', $e->concepto('A')->codigoError);
        self::assertSame('DSL_CICLO_DEPENDENCIAS', $e->concepto('B')->codigoError);
        self::assertTrue($e->concepto('BASICO')->esCalculado());
    }
}
