<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\BaseDeTests;
use Tests\TestCase;

/**
 * Verifica el guardarraíl que impide correr tests (y borrar tablas) contra la
 * base compartida `swnov`. Si este test falla, NO se corre ningún otro test.
 */
final class GuardarrailBaseDeTestsTest extends TestCase
{
    public function test_la_conexion_de_tests_es_una_base_dedicada(): void
    {
        [$conexion, $base] = BaseDeTests::asegurar();

        $this->assertContains($base, BaseDeTests::PERMITIDAS[$conexion]);
        $this->assertNotSame('swnov', $base, 'Los tests jamás pueden apuntar a la base compartida con el portal Drakma.');

        if ($conexion !== 'sqlite') {
            $this->assertSame($base, DB::connection($conexion)->getDatabaseName());
        }
    }

    #[DataProvider('basesProhibidas')]
    public function test_aborta_si_la_base_no_esta_en_la_lista_blanca(string $conexion, string $base): void
    {
        config(['database.default' => $conexion, "database.connections.{$conexion}.database" => $base]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ABORTADO/');

        BaseDeTests::asegurar();
    }

    public static function basesProhibidas(): array
    {
        return [
            'mysql sobre la base compartida' => ['mysql', 'swnov'],
            'mysql sobre otra base real' => ['mysql', 'swnov2024'],
            'mysql sin base' => ['mysql', ''],
            'sqlite en archivo' => ['sqlite', '/var/www/html/payroll2025/database/database.sqlite'],
            'conexión inexistente' => ['pgsql', 'payroll2025_test'],
        ];
    }
}
