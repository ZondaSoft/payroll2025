<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema base para los tests que no son del motor (auth, perfil, ejemplo).
 *
 * No usa RefreshDatabase a propósito: `migrate:fresh` NO puede funcionar en este
 * proyecto. payroll2025 se monta sobre un esquema que no le pertenece — las tablas
 * `sue*` y `sicoss*` las crea el portal Drakma (/var/www/html/swnov2024) y payroll
 * solo las ALTERA: hay 36 migraciones que tocan 12 tablas que ninguna migración de
 * este repo crea. En una base vacía, la primera de esas migraciones revienta.
 *
 * Por eso se corren únicamente las migraciones que crean tablas propias de la app
 * (usuarios, sesiones, colas y permisos), que es todo lo que necesitan estos tests.
 * Como son las migraciones reales, además verifican que sigan pudiendo correr desde cero.
 */
trait UsaEsquemaApp
{
    /** Laravel lo invoca solo, por convención setUp{NombreDelTrait}. */
    protected function setUpUsaEsquemaApp(): void
    {
        BaseDeTests::asegurar('preparar el esquema base de tests');

        if (BaseDeTests::$esquemaMontado !== 'app') {
            Schema::dropAllTables();
            EsquemaAppMinimo::crearTablasCompartidas();
            $this->artisan('migrate', [
                '--path' => [
                    'database/migrations/0001_01_01_000000_create_users_table.php',
                    'database/migrations/0001_01_01_000002_create_jobs_table.php',
                    'database/migrations/2025_12_11_173741_create_permission_tables.php',
                ],
                '--force' => true,
            ])->assertSuccessful();
            BaseDeTests::$esquemaMontado = 'app';
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }
}
