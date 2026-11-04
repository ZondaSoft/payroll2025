<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use PHPUnit\Framework\TestCase;

final class CodigoBloqueoTest extends TestCase
{
    /**
     * Bloqueo::aArray() pide la etiqueta de cada código: un caso sin etiqueta tira
     * UnhandledMatchError y tumba el simulador justo cuando hay algo que explicar
     * (pasaba con BASE_NO_CALCULADA al no poder derivar REM_10).
     */
    public function test_todos_los_codigos_tienen_etiqueta(): void
    {
        foreach (CodigoBloqueo::cases() as $codigo) {
            self::assertNotSame('', $codigo->etiqueta(), $codigo->value);
        }
    }
}
