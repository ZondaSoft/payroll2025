<?php

namespace Tests\Unit\Motor\ValorObjetos;

use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ImporteTest extends TestCase
{
    public function test_aritmetica_exacta_escala_6(): void
    {
        $i = Importe::de('0.1')->mas(Importe::de('0.2'));

        self::assertSame('0.300000', (string) $i);
        self::assertSame('0.30', $i->aTexto(2));
    }

    public function test_division_con_escala_y_redondeo(): void
    {
        self::assertSame('7009.509750', (string) Importe::de('1401901.95')->dividido(200));
        self::assertSame('7009.51', Importe::de('1401901.95')->dividido(200)->redondeado(2)->aTexto(2));
    }

    public function test_porcentaje_aplicado(): void
    {
        $base = Importe::de('1000000');

        self::assertSame('110000.00', Porcentaje::deFraccion('0.11')->aplicarA($base)->aTexto(2));
        self::assertSame('204000.00', Porcentaje::dePorciento('20.40')->aplicarA($base)->aTexto(2));
        self::assertSame('20.4000', Porcentaje::dePorciento('20.40')->porciento());
    }

    public function test_division_por_cero_bloquea(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Importe::de(1)->dividido(0);
    }

    public function test_json_es_string_sin_perder_escala(): void
    {
        self::assertSame('"84.802650"', json_encode(Importe::de('84.80265')));
    }
}
