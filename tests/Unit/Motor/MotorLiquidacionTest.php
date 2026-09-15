<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Liquidador\MotorLiquidacion;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class MotorLiquidacionTest extends TestCase
{
    public function test_aguirre_cct_371_con_horas_extras_en_prueba(): void
    {
        $liq = (new MotorLiquidacion)->liquidar(
            F::contextoPeriodo(Entorno::Prueba),
            F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), [F::novedad(3, 'HE_50', '9.5'), F::novedad(3, 'HE_100', '12')]),
            F::valoresParametros(), F::basicos(),
        );
        $h = [];
        foreach ($liq->haberes as $l) {
            $h[$l->codigo()] = $l->importe->aTexto(2);
        }

        self::assertSame('CON_AVISOS', $liq->estado(), implode(' | ', array_map(fn ($b) => $b->motivo, $liq->bloqueos)));
        self::assertSame('1335827.18', $h['BASICO']);
        self::assertSame('213732.35', $h['ANTIG'], '8 años × 2 % = 16 %');
        self::assertSame('66791.36', $h['ADTIT']);
        self::assertSame('106866.17', $h['ASIST'], '8 % de 1.335.827,18');
        self::assertSame('106866.17', $h['MANFON']);
        // BASE_HORA_NORMAL = BASICO+ANTIG+ADTIT+ASIST+MANFON = 1.830.083,23 ; jornada 48 hs → 208 hs/mes → hora 8.798,48
        self::assertSame('125378.30', $h['HE50'], '9,5 hs × 8.798,48 × 1,5');
        self::assertSame('211163.45', $h['HE100'], '12 hs × 8.798,48 × 2');
        self::assertArrayNotHasKey('KMREC', $h, 'KMREC es solo convenio 2');
        self::assertArrayNotHasKey('INDANT', $h, 'INDANT solo en FINAL');

        // bases ARCA: todos los remunerativos suman; REM_1 topeada al máximo de julio
        $totalRem = $liq->totalRemunerativo()->aTexto(2);
        self::assertSame('2166624.98', $totalRem);
        self::assertSame($totalRem, $liq->basesArca['REM_1']->importeAntesAjustes->aTexto(2));
        self::assertSame($totalRem, $liq->basesArca['REM_1']->importeAplicado->aTexto(2), 'debajo del tope máximo 4.509.567,41');
        self::assertSame($totalRem, $liq->basesArca['REM_9']->importeAplicado->aTexto(2));

        // aportes 11 % + 3 % sobre bases topeadas; contribuciones B = 18 % sobre REM_2/REM_3
        $ap = [];
        foreach ($liq->aportes as $a) {
            $ap[$a->codigoConcepto] = $a->importe->aTexto(2);
        }
        self::assertSame('238328.75', $ap['APJUB'], '11 % de la base REM_1');
        self::assertSame('64998.75', $ap['APINSSJYP'], '3 % de la base REM_5');
        self::assertSame('18.0000', (string) array_reduce($liq->contribuciones, fn ($t, $c) => $t->mas($c->porcentaje), \App\Motor\Dominio\ValorObjetos\Porcentaje::cero())->porciento());
        self::assertSame('389992.49', $liq->totalContribuciones()->aTexto(2), '18 % de 2.166.624,98 (B: 10,77+1,59+4,70+0,94)');
        self::assertSame('1863297.48', $liq->neto()->aTexto(2));

        // avisos por SIN_CONFIRMAR (entorno PRUEBA), sin bloqueos
        self::assertContains('SIN_CONFIRMAR', array_map(fn ($a) => $a->codigo, $liq->avisos));
        self::assertSame([], $liq->bloqueos);
    }

    public function test_alfaro_camionero_cct_40_con_kilometros_y_adicional_2(): void
    {
        $liq = (new MotorLiquidacion)->liquidar(
            F::contextoPeriodo(Entorno::Prueba),
            F::contexto(F::relacionAlfaro(), 2, F::modeloAntiguedadLineal(), [F::novedad(4, 'KM_REM', '2454'), F::novedad(4, 'CTRL_CARGA_DESC', '6'), F::novedad(4, 'PERM_FR_B', '3'), F::novedad(4, 'KM_VIAT_NR', '11900'), F::novedad(4, 'PERM_FR_A', '3')]),
            F::valoresParametros(), F::basicos(),
        );
        $h = [];
        foreach ($liq->haberes as $l) {
            $h[$l->codigo()] = $l->importe->aTexto(2);
        }

        self::assertSame([], array_map(fn ($b) => $b->motivo, $liq->bloqueos));
        self::assertSame('1060010.29', $h['BASICO']);
        self::assertSame('180201.75', $h['ANTIG'], '17 años × 1 % lineal');
        self::assertSame('208105.70', $h['KMREC'], '2454 × 84,80265');
        self::assertSame('265002.60', $h['CTRLCD'], '6 × 44.167,10');
        self::assertSame('90013.59', $h['PERMFRB']);
        self::assertSame('1009151.54', $h['VIATKM'], '11900 × 84,80265 no remunerativo');
        self::assertSame('171726.60', $h['PERMFRA']);
        self::assertSame('236175.63', $h['ADICNR20'], '20 % de BASE_NO_REMUNERATIVA (VIATKM + PERMFRA)');
        self::assertArrayNotHasKey('ADTIT', $h, 'ADTIT aplica solo a convenio 1');
        self::assertSame('CON_AVISOS', $liq->estado());
    }

    public function test_conceptos_sin_parametro_para_el_convenio_quedan_bloqueados_no_en_cero(): void
    {
        // forzamos ADTIT/ASIST/MANFON como GENERAL: para convenio 2 no tienen parámetro → bloqueo (nunca cero)
        $extra = [];
        foreach (['ADTIT', 'ASIST', 'MANFON'] as $cod) {
            $extra[$cod] = [new \App\Motor\Dominio\Entidades\ReglaAplicabilidad(null, $cod, \App\Motor\Dominio\Enums\TipoAmbito::General, null, null, null, null, \App\Motor\Dominio\Enums\AccionAplicabilidad::Incluir, F::vig(F::VIGENCIA), EstadoValidacion::SinConfirmar)];
        }
        $periodo = F::contextoPeriodo(Entorno::Prueba, versiones: F::versiones(aplicabilidadExtra: $extra));
        $liq = (new MotorLiquidacion)->liquidar($periodo, F::contexto(F::relacionAlfaro(), 2, F::modeloAntiguedadLineal(), [F::novedad(4, 'KM_REM', '1')]), F::valoresParametros(), F::basicos());
        $bloqueados = [];
        foreach ($liq->bloqueos as $b) {
            $bloqueados[$b->concepto] = $b->codigo;
        }
        // PORC_TITULO / PORC_ASISTENCIA / PORC_MANEJO_FONDOS solo existen para convenio 1
        self::assertSame(CodigoBloqueo::ParametroSinValor, $bloqueados['ADTIT']);
        self::assertSame(CodigoBloqueo::ParametroSinValor, $bloqueados['ASIST']);
        self::assertSame(CodigoBloqueo::ParametroSinValor, $bloqueados['MANFON']);
        self::assertSame('BLOQUEADO', $liq->estado());
        self::assertNotEmpty($liq->haberes, 'los demás conceptos se liquidan igual (bloqueos acumulados, no excepción única)');
    }

    public function test_en_produccion_todo_sin_confirmar_bloquea_en_varias_etapas(): void
    {
        $liq = (new MotorLiquidacion)->liquidar(F::contextoPeriodo(Entorno::Produccion), F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), []), F::valoresParametros(), F::basicos());

        self::assertSame('BLOQUEADO', $liq->estado());
        $codigos = array_unique(array_map(fn ($b) => $b->codigo->value, $liq->bloqueos));
        self::assertContains('ESTADO_NO_HABILITADO', $codigos);
        $etapas = array_unique(array_map(fn ($b) => $b->etapa, $liq->bloqueos));
        self::assertGreaterThan(1, count($etapas), 'bloqueos en más de una etapa (haberes, topes, aportes, contribuciones): '.implode(',', $etapas));
    }

    public function test_incidencia_pendiente_bloquea_bases_en_produccion(): void
    {
        $versiones = F::versiones(pendientes: ['BASICO']);
        $periodo = F::contextoPeriodo(Entorno::Produccion, versiones: $versiones, estadoNormativa: EstadoValidacion::Confirmado);
        $valores = array_map(fn ($v) => new \App\Motor\Dominio\Entidades\ValorParametro($v->id, $v->idParametro, $v->codigoParametro, $v->alcance, $v->idConvenio, $v->idCategoria, $v->idEmpresa, $v->relacionLaboralId, $v->valor, $v->vigencia, EstadoValidacion::Confirmado), F::valoresParametros());
        $basicos = [];
        foreach (F::basicos() as $cat => $lista) {
            $basicos[$cat] = [new \App\Motor\Dominio\Entidades\Basico($lista[0]->id, $cat, $lista[0]->codigoCategoria, $lista[0]->vigencia, $lista[0]->importe, EstadoValidacion::Confirmado)];
        }
        $ctx = F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), []);
        $liq = (new MotorLiquidacion)->liquidar($periodo, $ctx, $valores, $basicos);

        $codigos = array_map(fn ($b) => $b->codigo, $liq->bloqueos);
        self::assertContains(CodigoBloqueo::IncidenciaPendiente, $codigos);
        self::assertSame('BLOQUEADA', $liq->etapas['bases_arca']);
        self::assertNotEmpty($liq->haberes, 'los haberes se calcularon igual');
    }

    public function test_sin_encuadramiento_bloquea_solo_contribuciones(): void
    {
        $liq = (new MotorLiquidacion)->liquidar(F::contextoPeriodo(Entorno::Prueba), F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), [], encuadramiento: null), F::valoresParametros(), F::basicos());

        self::assertSame(['ENCUADRAMIENTO_AUSENTE'], array_values(array_unique(array_map(fn ($b) => $b->codigo->value, $liq->bloqueos))));
        self::assertSame('BLOQUEADA', $liq->etapas['contribuciones']);
        self::assertNotEmpty($liq->aportes, 'los aportes no dependen del encuadramiento');
    }

    public function test_dias_liquidados_proporcionan_basico_y_todo_lo_derivado(): void
    {
        $liq = (new MotorLiquidacion)->liquidar(F::contextoPeriodo(Entorno::Prueba), F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), [], dias: 15), F::valoresParametros(), F::basicos());
        $h = [];
        foreach ($liq->haberes as $l) {
            $h[$l->codigo()] = $l->importe->aTexto(2);
        }
        self::assertSame('667913.59', $h['BASICO']);
        self::assertSame('106866.17', $h['ANTIG']);
    }
}
