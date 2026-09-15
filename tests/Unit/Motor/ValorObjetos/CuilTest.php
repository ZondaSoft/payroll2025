<?php

namespace Tests\Unit\Motor\ValorObjetos;

use App\Motor\Dominio\ValorObjetos\Cuil;
use PHPUnit\Framework\TestCase;

final class CuilTest extends TestCase
{
    public function test_valida_digito_verificador(): void
    {
        self::assertTrue(Cuil::esValido('20-12345678-6'));
        self::assertFalse(Cuil::esValido('20-12345678-7'));
        self::assertSame('20123456786', (string) Cuil::de('20123456786'));
        self::assertSame('20-12345678-6', Cuil::de('20123456786')->formateado());
    }
}
