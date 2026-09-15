<?php

namespace Tests\Unit\Motor\ValorObjetos;

use App\Motor\Dominio\ValorObjetos\Vigencia;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VigenciaTest extends TestCase
{
    public function test_contiene_y_abierta(): void
    {
        $v = Vigencia::de('2026-07-01');

        self::assertTrue($v->esAbierta());
        self::assertTrue($v->contiene(Vigencia::parsear('2030-01-01')));
        self::assertFalse($v->contiene(Vigencia::parsear('2026-06-30')));
    }

    public function test_superposicion(): void
    {
        $a = Vigencia::de('2026-01-01', '2026-06-30');
        $b = Vigencia::de('2026-06-30');
        $c = Vigencia::de('2026-07-01');

        self::assertTrue($a->seSuperponeCon($b));
        self::assertFalse($a->seSuperponeCon($c));
    }

    public function test_cerrada_el_dia_anterior(): void
    {
        $v = Vigencia::de('2026-01-01')->cerradaAntesDe(Vigencia::parsear('2026-08-01'));

        self::assertSame('2026-07-31', $v->hasta?->format('Y-m-d'));
    }

    public function test_hasta_anterior_a_desde_es_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Vigencia::de('2026-07-01', '2026-06-30');
    }
}
