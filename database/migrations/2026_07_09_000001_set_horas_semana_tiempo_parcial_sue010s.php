<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Setea horas_semana = 24 (media jornada) en la jornada "Tiempo parcial".
 * El seeder 2026_06_06_000004 la cargó con horas_semana = NULL, y sin ese valor el
 * factor de prorrateo del LSD queda en 1 → la detracción NO se prorratea (se informa
 * el importe completo) → ARCA rechaza "el importe a detraer supera el tope".
 * Idempotente: solo completa las que están en NULL/0, no pisa horas ya cargadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sue010s')
            ->where('detalle', 'Tiempo parcial')
            ->where('parcial', 1)
            ->where(function ($q) {
                $q->whereNull('horas_semana')->orWhere('horas_semana', 0);
            })
            ->update(['horas_semana' => 24, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // No revierte: dejar 24 no rompe nada y evita reintroducir el bug.
    }
};
