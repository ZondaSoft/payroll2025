<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * GUARDARRAÍL ÚNICO de los tests (incidente 2026-09-15).
 *
 * La base `swnov` es compartida con el portal RRHH Drakma y contiene datos
 * reales. Si la configuración quedó cacheada (bootstrap/cache/config.php), los
 * <env> de phpunit.xml se IGNORAN y la conexión activa pasa a ser la de
 * producción: cualquier test con RefreshDatabase ejecutaría migrate:fresh
 * contra `swnov` y la destruiría (ya ocurrió una vez).
 *
 * Por eso nada se valida por "entorno" ni por "si es testing": se valida el
 * NOMBRE REAL de la base contra una lista blanca cerrada. Fuera de esta lista
 * ningún test corre y ninguna tabla se borra.
 */
final class BaseDeTests
{
    /**
     * Qué esquema está montado ahora mismo en la base de tests ('app' | 'motor' | null).
     *
     * Los tests de la app y los del motor levantan esquemas distintos y excluyentes sobre
     * la MISMA base, así que cada grupo debe reconstruir el suyo cuando el montado es otro.
     * Vive acá (y no en un trait) porque una propiedad estática de trait es privada de cada
     * clase que lo usa, y se recrearía el esquema una vez por clase de test.
     */
    public static ?string $esquemaMontado = null;

    /** Únicas bases sobre las que se permite correr tests y borrar tablas. */
    public const PERMITIDAS = [
        'mysql' => ['payroll2025_test'],
        'sqlite' => [':memory:'],
    ];

    /**
     * Devuelve [conexión, base] si es una base de tests legítima; aborta si no.
     *
     * @return array{0: string, 1: string}
     */
    public static function asegurar(string $motivo = 'correr tests'): array
    {
        $conexion = (string) config('database.default');
        $base = (string) config("database.connections.{$conexion}.database");
        $permitidas = self::PERMITIDAS[$conexion] ?? [];

        if (! in_array($base, $permitidas, true)) {
            throw new RuntimeException(
                "ABORTADO antes de {$motivo}: la conexión activa es '{$conexion}' sobre la base '{$base}', "
                .'que NO es una base de tests. Permitidas: '
                .implode(', ', array_map(
                    fn ($c, $bs) => $c.' → '.implode('/', $bs),
                    array_keys(self::PERMITIDAS),
                    self::PERMITIDAS
                ))
                .'. Si existe bootstrap/cache/config.php, la config cacheada pisa phpunit.xml: '
                .'ejecutá `php artisan config:clear` y volvé a correr. '
                .'Correr tests contra `swnov` destruiría la base compartida con el portal Drakma.'
            );
        }

        // Defensa en profundidad: preguntarle a la propia conexión en qué base está
        // parada (config y conexión real podrían divergir por un connection override).
        if ($conexion !== 'sqlite') {
            $real = (string) DB::connection($conexion)->getDatabaseName();
            if (! in_array($real, $permitidas, true)) {
                throw new RuntimeException(
                    "ABORTADO antes de {$motivo}: la conexión '{$conexion}' está realmente parada sobre "
                    ."la base '{$real}', que no es una base de tests."
                );
            }
        }

        return [$conexion, $base];
    }
}
