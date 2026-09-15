<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\ReglaRedondeo;
use App\Motor\Dominio\Servicios\Redondeador;
use App\Motor\Dominio\ValorObjetos\Importe;
use PHPUnit\Framework\TestCase;

final class RedondeadorTest extends TestCase
{
    public function test_reglas_del_mockup(): void
    {
        $r = new Redondeador(2);
        $i = Importe::de('7009.509750');

        self::assertSame('7009.509750', (string) $r->redondear($i, ReglaRedondeo::SinRedondeo));
        self::assertSame('7009.510000', (string) $r->redondear($i, ReglaRedondeo::Decimal));
        self::assertSame('7010.000000', (string) $r->redondear($i, ReglaRedondeo::Entero));
        self::assertSame('7009.510000', (string) $r->redondear($i, ReglaRedondeo::Parametrizada));
        self::assertSame('7009.509800', (string) (new Redondeador(4))->redondear($i, ReglaRedondeo::Parametrizada));
        self::assertSame('7009.509750', (string) $r->redondear($i, null));
    }
}
