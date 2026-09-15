<?php

namespace Tests\Unit\Motor;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Garantiza la regla hexagonal del módulo Motor (RT-003 de la spec):
 * Dominio y Aplicacion no conocen Laravel, Eloquent, Carbon ni helpers globales.
 * No bootea la aplicación: es PHPUnit puro sobre los tokens de cada archivo.
 */
final class ArquitecturaTest extends TestCase
{
    private const PERMITIDOS_COMUNES = [
        'App\Motor\Dominio\\',
        'Brick\Math\\',
        'DateTimeImmutable', 'DateTimeInterface', 'DateTimeZone', 'DateInterval', 'DatePeriod',
        'JsonSerializable', 'Stringable', 'Countable', 'IteratorAggregate', 'ArrayIterator', 'Traversable',
        'InvalidArgumentException', 'DomainException', 'LogicException', 'RuntimeException', 'Throwable',
        'Closure', 'BackedEnum', 'UnitEnum', 'Generator',
    ];

    private const CAPAS = [
        'Dominio' => [],
        'Aplicacion' => ['App\Motor\Aplicacion\\'],
    ];

    private const HELPERS_PROHIBIDOS = [
        'config', 'app', 'resolve', 'request', 'auth', 'now', 'today', 'session', 'env', 'collect', 'dd', 'dump',
        'logger', 'info', 'event', 'dispatch', 'response', 'redirect', 'route', 'view', 'abort', 'cache',
        'trans', '__', 'old', 'back', 'url', 'asset', 'bcrypt', 'encrypt', 'decrypt', 'report', 'validator',
        'once', 'optional', 'rescue', 'retry', 'tap', 'throw_if', 'throw_unless',
    ];

    public function test_dominio_y_aplicacion_no_dependen_del_framework(): void
    {
        $base = dirname(__DIR__, 3).'/app/Motor/';
        $violaciones = [];

        foreach (self::CAPAS as $capa => $extra) {
            $permitidos = array_merge(self::PERMITIDOS_COMUNES, $extra);

            foreach ($this->archivosPhp($base.$capa) as $archivo) {
                $relativo = substr($archivo->getPathname(), strlen($base));
                $tokens = token_get_all((string) file_get_contents($archivo->getPathname()));
                $total = count($tokens);

                for ($i = 0; $i < $total; $i++) {
                    $t = $tokens[$i];
                    if (! is_array($t)) {
                        continue;
                    }

                    if ($t[0] === T_NAME_QUALIFIED || $t[0] === T_NAME_FULLY_QUALIFIED) {
                        $nombre = ltrim($t[1], '\\');
                        if (str_starts_with($nombre, 'App\Motor\Dominio') || str_starts_with($nombre, 'App\Motor\Aplicacion')) {
                            // dirección de dependencia: Dominio no puede mirar a Aplicacion
                            if ($capa === 'Dominio' && str_starts_with($nombre, 'App\Motor\Aplicacion')) {
                                $violaciones[] = "{$relativo}: Dominio referencia Aplicacion ({$nombre})";
                            }
                            if (str_starts_with($nombre, 'App\Motor\Infraestructura')) {
                                $violaciones[] = "{$relativo}: referencia Infraestructura ({$nombre})";
                            }
                            // la propia capa o la inferior: permitido
                            if (! $this->permitido($nombre, $permitidos)) {
                                $violaciones[] = "{$relativo}: referencia no permitida '{$nombre}'";
                            }

                            continue;
                        }
                        if (! $this->permitido($nombre, $permitidos)) {
                            $violaciones[] = "{$relativo}: referencia no permitida '{$nombre}'";
                        }
                    }

                    if ($t[0] === T_STRING && in_array(strtolower($t[1]), self::HELPERS_PROHIBIDOS, true)
                        && $this->esLlamadaDeFuncionGlobal($tokens, $i)) {
                        $violaciones[] = "{$relativo}: usa el helper global '{$t[1]}('";
                    }
                }
            }
        }

        self::assertSame([], $violaciones, "Violaciones de arquitectura:\n - ".implode("\n - ", $violaciones));
    }

    /** @param list<string> $permitidos */
    private function permitido(string $nombre, array $permitidos): bool
    {
        foreach ($permitidos as $p) {
            if (str_ends_with($p, '\\') ? str_starts_with($nombre, $p) : $nombre === $p) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, mixed> $tokens */
    private function esLlamadaDeFuncionGlobal(array $tokens, int $i): bool
    {
        // siguiente token significativo debe ser '('
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (! isset($tokens[$j]) || $tokens[$j] !== '(') {
            return false;
        }
        // token anterior significativo no debe ser ->, ?->, ::, function, new, const
        $k = $i - 1;
        while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
            $k--;
        }
        if ($k < 0) {
            return true;
        }
        $prev = $tokens[$k];
        if (is_array($prev)) {
            return ! in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST], true);
        }

        return true;
    }

    /** @return iterable<SplFileInfo> */
    private function archivosPhp(string $directorio): iterable
    {
        if (! is_dir($directorio)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $archivo) {
            if ($archivo instanceof SplFileInfo && $archivo->getExtension() === 'php') {
                yield $archivo;
            }
        }
    }
}
