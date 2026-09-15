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
        $ns = 'App\\Motor\\Infraestructura\\Persistencia\\Eloquent\\Repositorios\\';
        foreach ([
            'RepositorioConceptos', 'RepositorioVersiones', 'RepositorioBasesArca', 'RepositorioSubsistemas', 'RepositorioAlicuotas',
            'RepositorioTopes', 'RepositorioRegimenes', 'RepositorioEncuadramientos', 'RepositorioParametros', 'RepositorioModelosAntiguedad',
            'RepositorioModelosVacaciones', 'RepositorioBasicos', 'RepositorioBasesZonda', 'RepositorioIncidenciasArca', 'RepositorioAplicabilidad',
            'RepositorioRelacionesLaborales', 'RepositorioIfRelacionOrigen', 'RepositorioNovedades', 'RepositorioIfNovedadOrigen', 'RepositorioTiposNovedad',
        ] as $puerto) {
            $this->app->bind('App\\Motor\\Aplicacion\\Puertos\\'.$puerto, $ns.$puerto.'Eloquent');
        }
        $this->app->bind(\App\Motor\Aplicacion\Puertos\UnidadDeTrabajo::class, $ns.'UnidadDeTrabajoEloquent');
        // Resultados: persistencia real es fase futura (sue090s/sue_cp090s pendientes de relevamiento).
        $this->app->singleton(\App\Motor\Aplicacion\Puertos\RepositorioResultados::class, \App\Motor\Infraestructura\EnMemoria\RepositorioResultadosEnMemoria::class);
    }

    private function registrarAdaptadoresLegacy(): void
    {
        $this->app->bind(\App\Motor\Aplicacion\Puertos\RepositorioCatalogosArca::class, \App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios\RepositorioCatalogosArcaEloquent::class);

        $ns = 'App\\Motor\\Infraestructura\\Legacy\\';
        foreach ([
            'ProveedorCategorias' => 'AdaptadorCategoriasSue006', 'ProveedorConvenios' => 'AdaptadorConveniosSue007',
            'ProveedorJornadas' => 'AdaptadorJornadasSue010', 'ProveedorEmpresas' => 'AdaptadorEmpresasSue086',
            'ProveedorLegajos' => 'AdaptadorLegajosSue001', 'ProveedorNovedadesOrigen' => 'AdaptadorNovedadesSue028',
            'ProveedorCatalogosSicoss' => 'AdaptadorCatalogosSicoss', 'ProveedorIncidenciasLegacy' => 'AdaptadorIncidenciasConceptosArca',
        ] as $puerto => $adaptador) {
            $this->app->bind('App\\Motor\\Aplicacion\\Puertos\\'.$puerto, $ns.$adaptador);
        }
    }
}
