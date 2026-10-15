<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Servicios\ConceptoEnBase;
use App\Motor\Dominio\Servicios\ConstructorBasesArca;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

/**
 * Reglas de composición de las REM_n contra la matriz S/N: instructivo §11–§12,
 * auditoría pre-entrega PE-2 y componerBaseAplicacion() de sim/motor_contributivo.js.
 */
final class ConstructorBasesArcaTest extends TestCase
{
    /** @return array<string, SubsistemaAplicacion> */
    private function aplicaciones(): array
    {
        $out = [];
        foreach (F::aplicaciones(EstadoValidacion::Confirmado) as $a) {
            $out[$a->clave()] = $a;
        }

        return $out;
    }

    /** @return array<string, list<IncidenciaArca>> */
    private function confirmadas(array $pendientes = []): array
    {
        return array_map(fn (array $lista) => array_map(fn (IncidenciaArca $i) => new IncidenciaArca(
            $i->id, $i->codigoConcepto, $i->idSubsistemaAplicacion, $i->codigoSubsistema, $i->tipoAplicacion,
            $i->incidencia, $i->vigencia, EstadoValidacion::Confirmado,
        ), $lista), F::incidenciasArca(F::conceptos(), $pendientes));
    }

    private function componer(array $conceptos, array $incidencias, Entorno $entorno = Entorno::Produccion, ?array $porRegimen = null): array
    {
        return (new ConstructorBasesArca)->componer($this->aplicaciones(), $incidencias, $conceptos, F::periodo()->ultimoDia(), new GateValidacion($entorno), $porRegimen);
    }

    public function test_remunerativos_integran_y_no_remunerativos_se_excluyen(): void
    {
        $c = F::conceptos();
        $haberes = [new LineaHaber($c['BASICO'], Importe::de('1000000'), Importe::de('1000000')), new LineaHaber($c['VIATKM'], Importe::de('50000'), Importe::de('50000'))];
        $r = (new ConstructorBasesArca)->construir($this->aplicaciones(), $this->confirmadas(), $haberes, F::periodo()->ultimoDia(), Entorno::Produccion);

        self::assertSame('1000000.00', $r['bases']['REM_1']->importeAplicado->aTexto(2));
        self::assertSame('1000000.00', $r['bases']['REM_9']->importeAplicado->aTexto(2));
        self::assertSame(['VIATKM' => 'incidencia N'], $r['bases']['REM_1']->excluidos);
        self::assertSame(['BASICO', 'VIATKM'], array_column($r['bases']['REM_1']->detalle, 'codigo_concepto'), 'el detalle conserva también los N');
        self::assertArrayNotHasKey('REM_6', $r['bases'], 'sin aplicación oficial no se construye');
        self::assertSame([], $r['avisos']);
    }

    public function test_los_importes_se_suman_exactos(): void
    {
        $r = $this->componer([
            ConceptoEnBase::calculado('BASICO', Importe::de('374748.9111456')),
            ConceptoEnBase::calculado('ANTIG', Importe::de('107464.7612844')),
        ], $this->confirmadas());

        self::assertSame('482213.672430', (string) $r['bases']['REM_1']->importeAplicado->valor, 'el redondeo es del aporte, no de la base');
    }

    public function test_incidencia_sin_confirmar_avisa_en_prueba_y_bloquea_en_produccion(): void
    {
        $sinConfirmar = F::incidenciasArca(F::conceptos());
        $base = [ConceptoEnBase::calculado('BASICO', Importe::de('100'))];

        $prueba = $this->componer($base, $sinConfirmar, Entorno::Prueba);
        self::assertFalse($prueba['bases']['REM_1']->estaBloqueada());
        self::assertContains('SIN_CONFIRMAR', array_map(fn ($a) => $a->codigo, $prueba['avisos']));

        $produccion = $this->componer($base, $sinConfirmar, Entorno::Produccion);
        self::assertTrue($produccion['bases']['REM_1']->estaBloqueada());
        self::assertSame('ESTADO_NO_HABILITADO', $produccion['bases']['REM_1']->codigoError);
    }

    public function test_pendiente_bloquea_la_base_en_cualquier_entorno(): void
    {
        foreach ([Entorno::Prueba, Entorno::Produccion] as $entorno) {
            $r = $this->componer([ConceptoEnBase::calculado('BASICO', Importe::de('100'))], $this->confirmadas(['BASICO']), $entorno);

            self::assertTrue($r['bases']['REM_1']->estaBloqueada(), $entorno->value);
            self::assertSame('INCIDENCIA_NO_RESUELTA', $r['bases']['REM_1']->codigoError);
            self::assertNull($r['bases']['REM_1']->aArray()['importe_aplicado'], 'una base bloqueada no vale cero');
        }
    }

    public function test_ausencia_de_fila_no_equivale_a_n(): void
    {
        // ACTAFUT calculado sin matriz vigente del acuerdo (instructivo §30): bloquea.
        $incid = $this->confirmadas();
        unset($incid['ACTAFUT']);
        $r = $this->componer([ConceptoEnBase::calculado('ACTAFUT', Importe::de('100'))], $incid, Entorno::Prueba);

        self::assertTrue($r['bases']['REM_1']->estaBloqueada());
        self::assertSame('INCIDENCIA_AUSENTE', $r['bases']['REM_1']->codigoError);
    }

    public function test_concepto_bloqueado_con_s_bloquea_la_base_con_su_codigo(): void
    {
        $r = $this->componer([
            ConceptoEnBase::calculado('BASICO', Importe::de('1000')),
            ConceptoEnBase::bloqueado('ANTIG', 'VAR_SIN_VALOR'),
        ], $this->confirmadas());

        self::assertTrue($r['bases']['REM_1']->estaBloqueada());
        self::assertSame('VAR_SIN_VALOR', $r['bases']['REM_1']->codigoError);
    }

    public function test_concepto_bloqueado_con_n_no_afecta_la_base(): void
    {
        $r = $this->componer([
            ConceptoEnBase::calculado('BASICO', Importe::de('1000')),
            ConceptoEnBase::bloqueado('VIATKM', 'PARAMETRO_SIN_VALOR'),
        ], $this->confirmadas());

        self::assertFalse($r['bases']['REM_1']->estaBloqueada());
        self::assertSame('1000.00', $r['bases']['REM_1']->importeAplicado->aTexto(2));
    }

    public function test_aplicacion_por_regimen_solo_compone_si_un_regimen_la_habilita(): void
    {
        $aps = $this->aplicaciones();
        $renatre = $aps['RENATRE|CONTRIBUCION'];
        $aps['RENATRE|CONTRIBUCION'] = new SubsistemaAplicacion($renatre->id, $renatre->idSubsistema, 'RENATRE', $renatre->tipoAplicacion,
            'REM_3', $renatre->columnaLsd, true, EstadoValidacion::Confirmado, SubsistemaAplicacion::REGIMEN);
        $conceptos = [ConceptoEnBase::calculado('BASICO', Importe::de('1000'))];
        $incid = $this->confirmadas();
        $fecha = F::periodo()->ultimoDia();

        $sin = (new ConstructorBasesArca)->componer($aps, $incid, $conceptos, $fecha, new GateValidacion(Entorno::Produccion), []);
        self::assertContains('RENATRE|CONTRIBUCION', array_column($sin['omitidas'], 'clave'));

        $con = (new ConstructorBasesArca)->componer($aps, $incid, $conceptos, $fecha, new GateValidacion(Entorno::Produccion), ['RENATRE']);
        self::assertSame([], $con['omitidas']);
    }

    public function test_dos_aplicaciones_de_la_misma_base_que_no_coinciden_la_bloquean(): void
    {
        $incid = $this->confirmadas();
        // INSSJP|CONTRIBUCION también compone REM_2, pero acá dice N para BASICO.
        $incid['BASICO'] = array_map(fn (IncidenciaArca $i) => $i->claveAplicacion() === 'INSSJP|CONTRIBUCION'
            ? new IncidenciaArca(null, 'BASICO', $i->idSubsistemaAplicacion, $i->codigoSubsistema, $i->tipoAplicacion, Incidencia::No, Vigencia::de('2026-01-01'), EstadoValidacion::Confirmado)
            : $i, $incid['BASICO']);

        $r = $this->componer([ConceptoEnBase::calculado('BASICO', Importe::de('1000'))], $incid);

        self::assertTrue($r['bases']['REM_2']->estaBloqueada());
        self::assertSame('BASE_ARCA_INCONSISTENTE_ENTRE_SUBSISTEMAS', $r['bases']['REM_2']->codigoError);
    }
}
