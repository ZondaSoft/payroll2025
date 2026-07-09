<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Catálogos de referencia SICOSS. Todos idempotentes (updateOrInsert/upsert/
        // updateOrCreate) → seguros de correr en cada deploy sin duplicar ni borrar.
        $this->call([
            Sicoss01Seeder::class,     // Actividades
            Sicoss05Seeder::class,     // Condiciones
            Sicoss08Seeder::class,     // Modalidades
            Sicoss12Seeder::class,     // Situaciones de revista
            SicossObrasSeeder::class,  // Obras sociales
            SicossSinieSeeder::class,  // Códigos de siniestro
            SicossZonasSeeder::class,  // Zonas / localidades
            Sue103Seeder::class,
        ]);
    }
}
