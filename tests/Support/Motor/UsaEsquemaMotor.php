<?php

namespace Tests\Support\Motor;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BaseDeTests;

/**
 * Prepara el esquema de los tests Feature del motor: las tablas legacy mínimas
 * que leen los adaptadores + SOLO las migraciones de database/migrations/motor.
 *
 * No usa RefreshDatabase, que correría también las 54 migraciones legacy.
 *
 * - MySQL (`payroll2025_test`, el modo real: es el motor que exige la spec y el
 *   único donde se verifican CHECK, collation y DECIMAL): el esquema se crea una
 *   vez por proceso y cada test corre dentro de una transacción que se revierte,
 *   así el aislamiento entre tests es el mismo que tenía sqlite :memory:.
 * - sqlite `:memory:`: esquema nuevo por test (no hay nada que revertir).
 *
 * Antes de tocar el esquema se valida la base contra la lista blanca de
 * Tests\Support\BaseDeTests: jamás puede borrar tablas de `swnov`.
 */
trait UsaEsquemaMotor
{
    protected function prepararEsquemaMotor(): void
    {
        [$conexion] = BaseDeTests::asegurar('preparar el esquema de tests del motor');

        if ($conexion === 'sqlite') {
            if (! extension_loaded('pdo_sqlite')) {
                $this->markTestSkipped('Falta la extensión pdo_sqlite (sudo apt install php8.3-sqlite3). Alternativa: correr los tests sobre MySQL apuntando phpunit.xml a payroll2025_test.');
            }
            EsquemaLegacyMinimo::crear();
            $this->migrarEsquemaMotor();

            return;
        }

        // El flag vive en EsquemaLegacyMinimo (no en el trait): una propiedad estática de un
        // trait es privada de cada clase que lo usa, y el esquema se recrearía por clase de test.
        // Se comprueba además que las tablas sigan ahí: otro test con RefreshDatabase (migrate:fresh)
        // sobre la misma base las habría borrado dejando el flag mintiendo.
        if (! EsquemaLegacyMinimo::$preparado || ! Schema::hasTable('motor_conceptos')) {
            Schema::dropAllTables();
            EsquemaLegacyMinimo::crear();
            $this->migrarEsquemaMotor();
            EsquemaLegacyMinimo::$preparado = true;
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    private function migrarEsquemaMotor(): void
    {
        $this->artisan('migrate', [
            '--path' => database_path('migrations/motor'),
            '--realpath' => true,
            '--force' => true,
        ])->assertSuccessful();
    }
}
