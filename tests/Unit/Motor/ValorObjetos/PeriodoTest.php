<?php

namespace Tests\Unit\Motor\ValorObjetos;

use App\Motor\Dominio\ValorObjetos\Periodo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PeriodoTest extends TestCase
{
    public function test_acepta_ambos_formatos_y_traduce(): void
    {
        $a = Periodo::de('2026-07');
        $b = Periodo::de('202607');

        self::assertTrue($a->igualA($b));
        self::assertSame('2026-07', (string) $a);
        self::assertSame('202607', $a->yyyymm());
    }

    public function test_primer_y_ultimo_dia_en_zona_argentina(): void
    {
        $p = Periodo::de('2026-02');

        self::assertSame('2026-02-01', $p->primerDia()->format('Y-m-d'));
        self::assertSame('2026-02-28', $p->ultimoDia()->format('Y-m-d'));
        self::assertSame('America/Argentina/Buenos_Aires', $p->ultimoDia()->getTimezone()->getName());
    }

    public function test_siguiente_anterior_y_meses(): void
    {
        self::assertSame('2027-01', (string) Periodo::de('2026-12')->siguiente());
        self::assertSame('2025-12', (string) Periodo::de('2026-01')->anterior());
        self::assertSame(48, Periodo::de('2030-05')->mesesDesde(Periodo::de('2026-05')));
    }

    public function test_rechaza_invalidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Periodo::de('2026-13');
    }
}
