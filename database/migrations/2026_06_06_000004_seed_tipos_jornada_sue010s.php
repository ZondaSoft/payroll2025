<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Carga/actualiza los tipos de jornada en sue010s.
     * Usa updateOrInsert (clave = detalle): si la jornada ya existe la actualiza, si no, la inserta.
     * Se matchea por `detalle` (no por id) para no pisar ids que ya puedan estar referenciados por legajos.
     */
    public function up(): void
    {
        $jornadas = [
            ['detalle' => 'Jornada completa',               'horas_semana' => 48,   'parcial' => false],
            ['detalle' => 'Tiempo parcial',                 'horas_semana' => 24,   'parcial' => true],  // media jornada (24/48). Necesario para prorratear la detracción del LSD.
            ['detalle' => 'Jornada reducida (insalubre)',   'horas_semana' => 36,   'parcial' => false],
            ['detalle' => 'Jornada nocturna',               'horas_semana' => 42,   'parcial' => false],
            ['detalle' => 'Jornada mixta',                  'horas_semana' => null, 'parcial' => false],
        ];

        foreach ($jornadas as $j) {
            DB::table('sue010s')->updateOrInsert(
                ['detalle' => $j['detalle']],
                [
                    'horas_semana' => $j['horas_semana'],
                    'parcial' => $j['parcial'],
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Revierte solo los valores cargados (deja las filas, que son catálogo).
        DB::table('sue010s')
            ->whereIn('detalle', [
                'Jornada completa',
                'Tiempo parcial',
                'Jornada reducida (insalubre)',
                'Jornada nocturna',
                'Jornada mixta',
            ])
            ->update(['horas_semana' => null, 'parcial' => false]);
    }
};
