<?php

namespace Tests\Support\Motor;

/**
 * Para tests Feature del motor: levanta el esquema legacy mínimo y SOLO las
 * migraciones de database/migrations/motor en sqlite :memory:.
 * No usa RefreshDatabase (que correría las 54 migraciones legacy y falla en sqlite).
 */
trait UsaEsquemaMotor
{
    protected function prepararEsquemaMotor(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Falta la extensión pdo_sqlite (sudo apt install php8.3-sqlite3): los tests Feature del motor corren solo en sqlite :memory:.');
        }
        EsquemaLegacyMinimo::crear();
        $this->artisan('migrate', [
            '--path' => database_path('migrations/motor'),
            '--realpath' => true,
            '--force' => true,
        ])->assertSuccessful();
    }
}
