<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Estrategias\RegistroEstrategias;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\EntornoCalculo;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Liquidador\Resultado\Traza;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\ResolutorAntiguedad;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class EstrategiasTest extends TestCase
{
    private function calc(): EntornoCalculo
    {
        $gate = new GateValidacion(Entorno::Prueba);

        return new EntornoCalculo(new ResolutorParametros(F::valoresParametros(), $gate), new ResolutorBasico(F::basicos(), $gate), new ResolutorAntiguedad, new Traza);
    }

    public function test_basico_proporcional_a_dias(): void
    {
        $c = F::conceptos()['BASICO'];
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []);
        $r = (new RegistroEstrategias)->para($c)->calcular($c, $ctx, $this->calc());
        self::assertSame('1335827.18', $r->importe->aTexto(2), 'categoría 104 con 30 días');

        $ctx15 = F::contexto(F::relacionAguirre(), 1, null, [], dias: 15);
        self::assertSame('667913.59', (new RegistroEstrategias)->para($c)->calcular($c, $ctx15, $this->calc())->importe->aTexto(2));
    }

    public function test_antiguedad_escala_sobre_basico_devengado(): void
    {
        $c = F::conceptos()['ANTIG'];
        $ctx = F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), []);
        $r = (new RegistroEstrategias)->para($c)->calcular($c, $ctx, $this->calc());

        self::assertSame(8, $r->detalle['anios']);
        self::assertSame('213732.35', $r->importe->aTexto(2), '1.335.827,18 × 16 %');
    }

    public function test_porcentaje_sobre_basico(): void
    {
        $c = F::conceptos()['ADTIT'];
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []);
        self::assertSame('66791.36', (new RegistroEstrategias)->para($c)->calcular($c, $ctx, $this->calc())->importe->aTexto(2), '5 % del básico');
    }

    public function test_cantidad_por_valor_con_tarifa(): void
    {
        $c = F::conceptos()['KMREC'];
        $ctx = F::contexto(F::relacionAlfaro(), 2, null, [F::novedad(4, 'KM_REM', '2454')]);
        $r = (new RegistroEstrategias)->para($c)->calcular($c, $ctx, $this->calc());

        self::assertSame('2454', (string) $r->cantidad);
        self::assertSame('208105.70', $r->importe->aTexto(2), '2454 km × 84,80265');
    }

    public function test_horas_extras_sobre_hora_normal_de_la_base(): void
    {
        $conceptos = F::conceptos();
        $calc = $this->calc();
        $ctx = F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), [F::novedad(3, 'HE_50', '9.5'), F::novedad(3, 'HE_100', '12')]);
        // la base hora normal se arma con los haberes ya ejecutados
        $calc->integrantesBases = F::composicionBases();
        $reg = new RegistroEstrategias;
        foreach (['BASICO', 'ANTIG'] as $cod) {
            $r = $reg->para($conceptos[$cod])->calcular($conceptos[$cod], $ctx, $calc);
            $calc->haberes[] = new LineaHaber($conceptos[$cod], $r->importe->redondeado(2), $r->importe);
        }
        // base = 1.335.827,18 + 213.732,35 = 1.549.559,53 ; jornada 48 hs → 208 hs/mes → hora = 7.449,80
        $he50 = $reg->para($conceptos['HE50'])->calcular($conceptos['HE50'], $ctx, $calc);
        self::assertSame('7449.805433', (string) $he50->valorUnitario);
        self::assertSame('106159.73', $he50->importe->aTexto(2), '9,5 hs × 7.449,81 × 1,5');
        $he100 = $reg->para($conceptos['HE100'])->calcular($conceptos['HE100'], $ctx, $calc);
        self::assertSame('178795.33', $he100->importe->aTexto(2), '12 hs × 7.449,81 × 2');
    }

    public function test_jornada_sin_dato_usa_horas_mes_referencia_con_aviso(): void
    {
        $conceptos = F::conceptos();
        $calc = $this->calc();
        $ctx = F::contexto(F::relacionAguirre(), 1, null, [F::novedad(3, 'HE_50', '1')], jornada: \App\Motor\Dominio\Contratos\JornadaCanonica::sinDato());
        $calc->integrantesBases = F::composicionBases();
        $reg = new RegistroEstrategias;
        $r = $reg->para($conceptos['BASICO'])->calcular($conceptos['BASICO'], $ctx, $calc);
        $calc->haberes[] = new LineaHaber($conceptos['BASICO'], $r->importe->redondeado(2), $r->importe);

        $he = $reg->para($conceptos['HE50'])->calcular($conceptos['HE50'], $ctx, $calc);
        self::assertSame('6679.135900', (string) $he->valorUnitario, '1.335.827,18 / 200 hs');
        self::assertContains('JORNADA_SIN_DATO', array_map(fn ($a) => $a->codigo, $ctx->avisos));
    }

    public function test_novedad_ausente_bloquea_si_es_obligatoria_y_omite_si_no(): void
    {
        $ctx = F::contexto(F::relacionAlfaro(), 2, null, []);
        // KMREC no exige novedad: sin km no se liquida (señal SinNovedad, no bloqueo ni cero)
        $c = F::conceptos()['KMREC'];
        try {
            (new RegistroEstrategias)->para($c)->calcular($c, $ctx, $this->calc());
            self::fail();
        } catch (\App\Motor\Dominio\Estrategias\SinNovedad $e) {
            self::assertSame('KM_REM', $e->codigoNovedad);
        }
        // con requiere_novedad = true la ausencia es un bloqueo
        $d = $c->aDefinicion();
        $d['requiere_novedad'] = true;
        $obligatorio = \App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, $c->id);
        try {
            (new RegistroEstrategias)->para($obligatorio)->calcular($obligatorio, $ctx, $this->calc());
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::NovedadObligatoriaAusente, $e->codigo);
        }
    }

    public function test_manual_no_se_ejecuta_y_modulo_sistema_no_es_haber(): void
    {
        $conceptos = F::conceptos();
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []);
        self::assertTrue((new RegistroEstrategias)->para($conceptos['ACTAFUT'])->calcular($conceptos['ACTAFUT'], $ctx, $this->calc())->manual);
        try {
            (new RegistroEstrategias)->para($conceptos['APJUB'])->calcular($conceptos['APJUB'], $ctx, $this->calc());
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::EstrategiaSinEjecutor, $e->codigo);
        }
    }
}
