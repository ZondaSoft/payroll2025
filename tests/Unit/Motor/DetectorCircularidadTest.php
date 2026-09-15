<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Servicios\DetectorCircularidad;
use PHPUnit\Framework\TestCase;

final class DetectorCircularidadTest extends TestCase
{
    public function test_directa_r13_consume_e_integra_la_misma_base(): void
    {
        $d = new DetectorCircularidad;
        $integrantes = ['BASE_HORA_NORMAL' => ['BASICO', 'HE50']];
        $consume = ['HE50' => 'BASE_HORA_NORMAL'];

        self::assertSame(['HE50'], $d->directas($integrantes, $consume));
        self::assertSame([], $d->directas(['BASE_HORA_NORMAL' => ['BASICO']], $consume));
    }

    public function test_indirecta_ciclo_entre_bases(): void
    {
        $d = new DetectorCircularidad;
        // C1 consume A e integra B; C2 consume B e integra A → A → B → A
        $integrantes = ['A' => ['C2', 'BASICO'], 'B' => ['C1']];
        $consume = ['C1' => 'A', 'C2' => 'B'];

        $ciclos = $d->ciclos($integrantes, $consume);
        self::assertCount(1, $ciclos);
        self::assertSame(['A', 'B', 'A'], $ciclos[0]);

        self::assertSame([], $d->ciclos(['A' => ['BASICO'], 'B' => ['C1']], ['C1' => 'A']), 'A → B sin vuelta no es ciclo');
    }
}
