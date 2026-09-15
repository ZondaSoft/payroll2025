<?php

namespace App\Motor;

use App\Motor\Aplicacion\DTO\ConfiguracionMotor;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Infraestructura\RelojSistema;
use Illuminate\Support\ServiceProvider;

/**
 * Único punto del módulo Motor que conoce el framework y la configuración.
 *
 * Acá se atan los puertos (interfaces de Aplicacion) con sus adaptadores
 * (Infraestructura). Dominio y Aplicacion no dependen de Laravel: lo garantiza
 * tests/Unit/Motor/ArquitecturaTest.php.
 *
 * Bindings transitorios (bind) para repositorios y adaptadores: no hay
 * construcción cara y así ningún estado se filtra entre corridas ni entre tests.
 * Singleton solo para el Reloj y la configuración (inmutables).
 */
class MotorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfiguracionMotor::class, function () {
            return new ConfiguracionMotor(
                entorno: Entorno::from((string) config('motor.entorno', 'PRUEBA')),
                conceptosMinimosSistema: (array) config('motor.conceptos_minimos_sistema', []),
            );
        });

        $this->app->singleton(Reloj::class, RelojSistema::class);

        // Los bindings puerto → repositorio/adaptador se registran en registrarPersistencia()
        // y registrarAdaptadoresLegacy() (fase F2).
        $this->registrarPersistencia();
        $this->registrarAdaptadoresLegacy();
    }

    public function boot(): void
    {
        // Las migraciones del motor viven en su propio directorio para poder
        // correrlas aisladas en los tests (sqlite :memory:) sin arrastrar las
        // migraciones legacy del resto del sistema.
        $this->loadMigrationsFrom(database_path('migrations/motor'));
    }

    private function registrarPersistencia(): void
    {
        // F2-b
    }

    private function registrarAdaptadoresLegacy(): void
    {
        // F2-c
    }
}
