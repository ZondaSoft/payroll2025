<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class ResolutorParametrosTest extends TestCase
{
    private function valor(int $id, string $codigo, AlcanceParametro $alcance, string $valor, ?int $conv = null, ?int $cat = null, ?int $emp = null, ?int $rl = null): ValorParametro
    {
        return new ValorParametro($id, 1, $codigo, $alcance, $conv, $cat, $emp, $rl, BigDecimal::of($valor), F::vig(), EstadoValidacion::Confirmado);
    }

    public function test_precedencia_trabajador_empresa_categoria_convenio_general(): void
    {
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []); // convenio 1, categoría 104, empresa 1, rl 3
        $valores = [
            $this->valor(1, 'X', AlcanceParametro::General, '1'),
            $this->valor(2, 'X', AlcanceParametro::Convenio, '2', conv: 1),
            $this->valor(3, 'X', AlcanceParametro::Categoria, '3', cat: 104),
            $this->valor(4, 'X', AlcanceParametro::Empresa, '4', emp: 1),
            $this->valor(5, 'X', AlcanceParametro::Trabajador, '5', rl: 3),
            $this->valor(6, 'X', AlcanceParametro::Categoria, '99', cat: 999), // otra categoría: no aplica
        ];
        $r = new ResolutorParametros($valores, new GateValidacion(Entorno::Produccion));
        self::assertSame('5', (string) $r->resolver('X', $ctx));

        $r = new ResolutorParametros(array_slice($valores, 0, 4), new GateValidacion(Entorno::Produccion));
        self::assertSame('4', (string) $r->resolver('X', $ctx));

        $r = new ResolutorParametros(array_slice($valores, 0, 2), new GateValidacion(Entorno::Produccion));
        self::assertSame('2', (string) $r->resolver('X', $ctx));
    }

    public function test_empate_en_el_mismo_nivel_bloquea(): void
    {
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []);
        $r = new ResolutorParametros([$this->valor(1, 'X', AlcanceParametro::Convenio, '2', conv: 1), $this->valor(2, 'X', AlcanceParametro::Convenio, '3', conv: 1)], new GateValidacion(Entorno::Prueba));

        try {
            $r->resolver('X', $ctx);
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::ParametroEmpate, $e->codigo);
        }
    }

    public function test_sin_valor_bloquea_y_sin_confirmar_avisa_en_prueba(): void
    {
        $ctx = F::contexto(F::relacionAguirre(), 1, null, []);
        $r = new ResolutorParametros([], new GateValidacion(Entorno::Prueba));
        try {
            $r->resolver('TARIFA_KM', $ctx);
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::ParametroSinValor, $e->codigo);
        }

        $r = new ResolutorParametros(F::valoresParametros(), new GateValidacion(Entorno::Prueba));
        self::assertSame('0.05', (string) $r->resolver('PORC_TITULO', $ctx)->stripTrailingZeros());
        self::assertSame('SIN_CONFIRMAR', $ctx->avisos[0]->codigo);
    }
}
