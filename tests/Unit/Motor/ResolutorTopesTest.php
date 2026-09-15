<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\BaseArca;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\ResolutorTopes;
use App\Motor\Dominio\ValorObjetos\Importe;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

/** Reglas A-H de TOPES_MINIMOS, una por test. */
final class ResolutorTopesTest extends TestCase
{
    private BaseArca $rem1;

    private BaseArca $rem2;

    protected function setUp(): void
    {
        $this->rem1 = new BaseArca('REM_1', 'Aportes SIPA', SujetoObligado::Trabajador, true);
        $this->rem2 = new BaseArca('REM_2', 'Contribuciones', SujetoObligado::Empleador, false);
    }

    private function tope(string $codigo, string $base, TipoTope $tipo, ?string $importe, EstadoValidacion $e = EstadoValidacion::Confirmado, ComponenteTope $c = ComponenteTope::Mensual): Tope
    {
        return new Tope(1, $codigo, F::periodo(), $base, $tipo, $c, $importe === null ? null : Importe::de($importe), $e);
    }

    private function esperarBloqueo(callable $fn, CodigoBloqueo $codigo): void
    {
        try {
            $fn();
            self::fail("Se esperaba bloqueo {$codigo->value}");
        } catch (MotorBloqueado $e) {
            self::assertSame($codigo, $e->codigo);
        }
    }

    public function test_regla_a_base_no_topeable_no_busca_tope(): void
    {
        $r = new ResolutorTopes([], new GateValidacion(Entorno::Produccion));
        $x = $r->aplicar($this->rem2, Importe::de('9000000'), ComponenteTope::Mensual);

        self::assertSame('9000000.00', $x['importe']->aTexto(2));
        self::assertNull($x['maximo']);
    }

    public function test_regla_b_base_topeable_sin_registro_bloquea(): void
    {
        $r = new ResolutorTopes([], new GateValidacion(Entorno::Produccion));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::TopeSinParametrizacion);
    }

    public function test_regla_c_importe_vacio_bloquea(): void
    {
        $r = new ResolutorTopes([$this->tope('T', 'REM_1', TipoTope::Maximo, null)], new GateValidacion(Entorno::Prueba));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::TopeImporteVacio);
    }

    public function test_minimo_sin_dato_se_omite_con_aviso_porque_es_opcional(): void
    {
        // spec: "los mínimos de REM_4 y REM_5 quedaron pendientes y no deben aplicarse hasta cerrarlos"
        $r = new ResolutorTopes([$this->tope('MAX', 'REM_1', TipoTope::Maximo, '4509567.41'), $this->tope('MIN', 'REM_1', TipoTope::Minimo, null, EstadoValidacion::SinDato)], new GateValidacion(Entorno::Produccion));
        $x = $r->aplicar($this->rem1, Importe::de('1000'), ComponenteTope::Mensual);

        self::assertSame('1000.00', $x['importe']->aTexto(2), 'el mínimo pendiente no eleva la base');
        self::assertNull($x['minimo']);
        self::assertSame('TOPE_MINIMO_SIN_DATO', $x['avisos'][0]->codigo);
    }

    public function test_regla_d_sin_dato_bloquea(): void
    {
        $r = new ResolutorTopes([$this->tope('T', 'REM_1', TipoTope::Maximo, '100', EstadoValidacion::SinDato)], new GateValidacion(Entorno::Prueba));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::EstadoNoHabilitado);
    }

    public function test_regla_e_sin_confirmar_bloquea_en_produccion_y_avisa_en_prueba(): void
    {
        $topes = [$this->tope('T', 'REM_1', TipoTope::Maximo, '4509567.41', EstadoValidacion::SinConfirmar)];
        $this->esperarBloqueo(fn () => (new ResolutorTopes($topes, new GateValidacion(Entorno::Produccion)))->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::EstadoNoHabilitado);

        $x = (new ResolutorTopes($topes, new GateValidacion(Entorno::Prueba)))->aplicar($this->rem1, Importe::de('5000000'), ComponenteTope::Mensual);
        self::assertSame('4509567.41', $x['importe']->aTexto(2));
        self::assertSame('SIN_CONFIRMAR', $x['avisos'][0]->codigo);
    }

    public function test_regla_f_duplicidad_bloquea(): void
    {
        $r = new ResolutorTopes([$this->tope('T1', 'REM_1', TipoTope::Maximo, '100'), $this->tope('T2', 'REM_1', TipoTope::Maximo, '200')], new GateValidacion(Entorno::Produccion));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::TopeDuplicado);
    }

    public function test_regla_g_no_arrastra_periodo_anterior(): void
    {
        // el repositorio entrega solo topes DEL período; un tope de junio no está en la lista de julio → regla B
        $r = new ResolutorTopes([], new GateValidacion(Entorno::Produccion));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::Mensual), CodigoBloqueo::TopeSinParametrizacion);
    }

    public function test_regla_h_no_arrastra_entre_componentes(): void
    {
        $r = new ResolutorTopes([$this->tope('T', 'REM_1', TipoTope::Maximo, '100', c: ComponenteTope::Mensual)], new GateValidacion(Entorno::Produccion));
        $this->esperarBloqueo(fn () => $r->aplicar($this->rem1, Importe::de('100'), ComponenteTope::SacCompleto), CodigoBloqueo::TopeSinParametrizacion);
    }

    public function test_aplica_maximo_y_minimo(): void
    {
        $r = new ResolutorTopes([$this->tope('MAX', 'REM_1', TipoTope::Maximo, '4509567.41'), $this->tope('MIN', 'REM_1', TipoTope::Minimo, '138757.90')], new GateValidacion(Entorno::Produccion));

        self::assertSame('4509567.41', $r->aplicar($this->rem1, Importe::de('6000000'), ComponenteTope::Mensual)['importe']->aTexto(2));
        self::assertSame('138757.90', $r->aplicar($this->rem1, Importe::de('50000'), ComponenteTope::Mensual)['importe']->aTexto(2));
        self::assertSame('1000000.00', $r->aplicar($this->rem1, Importe::de('1000000'), ComponenteTope::Mensual)['importe']->aTexto(2));
        self::assertSame('0.00', $r->aplicar($this->rem1, Importe::cero(), ComponenteTope::Mensual)['importe']->aTexto(2), 'base cero no se eleva al mínimo');
    }
}
