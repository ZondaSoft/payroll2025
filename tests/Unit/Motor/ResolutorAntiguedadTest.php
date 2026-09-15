<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Servicios\ResolutorAntiguedad;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class ResolutorAntiguedadTest extends TestCase
{
    public function test_escala_2_3_con_tope_70(): void
    {
        $r = new ResolutorAntiguedad;
        $m = F::modeloAntiguedadEscala();

        self::assertSame('0.0000', $r->porcentaje($m, 0)->porciento());
        self::assertSame('16.0000', $r->porcentaje($m, 8)->porciento());
        self::assertSame('40.0000', $r->porcentaje($m, 20)->porciento());
        self::assertSame('43.0000', $r->porcentaje($m, 21)->porciento());
        self::assertSame('70.0000', $r->porcentaje($m, 30)->porciento());
        self::assertSame('70.0000', $r->porcentaje($m, 45)->porciento(), 'tramo abierto + tope 70 %');
    }

    public function test_lineal_sin_tope_crece_sin_limite(): void
    {
        $r = new ResolutorAntiguedad;
        $m = F::modeloAntiguedadLineal();

        self::assertSame('17.0000', $r->porcentaje($m, 17)->porciento());
        self::assertSame('120.0000', $r->porcentaje($m, 120)->porciento(), 'sin porcentaje_maximo no hay tope (comportamiento extremo documentado en la spec)');
    }

    public function test_relacion_calcula_anios_completos_al_cierre_del_periodo(): void
    {
        self::assertSame(8, F::relacionAguirre()->aniosAntiguedadA(F::periodo()->ultimoDia()));
        self::assertSame(17, F::relacionAlfaro()->aniosAntiguedadA(F::periodo()->ultimoDia()));
    }
}
