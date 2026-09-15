<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Servicios\GateValidacion;
use PHPUnit\Framework\TestCase;

final class GateValidacionTest extends TestCase
{
    public function test_produccion_solo_admite_confirmado(): void
    {
        $g = new GateValidacion(Entorno::Produccion);

        self::assertNull($g->evaluar(EstadoValidacion::Confirmado, 'x', 'dato'));
        $this->expectException(MotorBloqueado::class);
        $g->evaluar(EstadoValidacion::SinConfirmar, 'x', 'dato');
    }

    public function test_prueba_admite_sin_confirmar_con_aviso_y_bloquea_sin_dato_obligatorio(): void
    {
        $g = new GateValidacion(Entorno::Prueba);

        $aviso = $g->evaluar(EstadoValidacion::SinConfirmar, 'motor_topes_minimos:TOP-003', 'Tope TOP-003');
        self::assertSame('SIN_CONFIRMAR', $aviso?->codigo);

        self::assertSame('SIN_DATO', $g->evaluar(EstadoValidacion::SinDato, 'x', 'dato', obligatorio: false)?->codigo);

        try {
            $g->evaluar(EstadoValidacion::SinDato, 'x', 'dato', obligatorio: true);
            self::fail('SIN_DATO obligatorio debe bloquear');
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::EstadoNoHabilitado, $e->codigo);
            self::assertFalse($e->fatal);
        }
    }
}
