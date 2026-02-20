<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sue103;

class Sue103Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tipo 1: HABER
        Sue103::create([
            'tipo' => 1,
            'desde' => 1,
            'hasta' => 1999,
        ]);

        // Tipo 2: DESCUENTO
        Sue103::create([
            'tipo' => 2,
            'desde' => 2000,
            'hasta' => 2999,
        ]);

        // Tipo 3: ASIGNACIONES
        Sue103::create([
            'tipo' => 3,
            'desde' => 3000,
            'hasta' => 3999,
        ]);

        // Tipo 4: NO_REMUNERATIVO
        Sue103::create([
            'tipo' => 4,
            'desde' => 4000,
            'hasta' => 4999,
        ]);

        // Tipo 5: GANANCIAS
        Sue103::create([
            'tipo' => 5,
            'desde' => 5000,
            'hasta' => 5999,
        ]);

        // Tipo 6: DEVOLUCIÓN DE GANANCIA
        Sue103::create([
            'tipo' => 6,
            'desde' => 6000,
            'hasta' => 6999,
        ]);

        // Tipo 7: REDONDEO
        Sue103::create([
            'tipo' => 7,
            'desde' => 9999,
            'hasta' => 9999,
        ]);

        // Tipo 8: APORTES
        Sue103::create([
            'tipo' => 8,
            'desde' => 8000,
            'hasta' => 8999,
        ]);

        // Tipo 9: AUXILIARES
        Sue103::create([
            'tipo' => 9,
            'desde' => 9500,
            'hasta' => 9599,
        ]);
    }
}
