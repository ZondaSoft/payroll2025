<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * GUARDARRAÍL (incidente 2026-09-15): la base `swnov` es compartida con el
     * portal RRHH y contiene datos reales. Si la configuración está cacheada
     * (bootstrap/cache/config.php), los <env> de phpunit.xml se ignoran y
     * cualquier test con RefreshDatabase ejecuta migrate:fresh contra MySQL.
     * Ningún test de este proyecto puede correr si la conexión no es sqlite :memory:.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $conexion = (string) config('database.default');
        $base = (string) config("database.connections.{$conexion}.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "ABORTADO: los tests deben correr en sqlite :memory: y la conexión activa es '{$conexion}' ({$base}). "
                .'Si existe bootstrap/cache/config.php, ejecutá `php artisan config:clear` antes de correr tests. '
                .'Correr tests contra MySQL destruiría la base compartida swnov.'
            );
        }
    }
}
