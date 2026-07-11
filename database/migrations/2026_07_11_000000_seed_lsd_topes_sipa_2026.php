<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Carga las bases imponibles SIPA (ANSES) por período en lsd_topes, para no cargarlas a mano.
 *
 * IDEMPOTENTE Y NO DESTRUCTIVA: usa insertOrIgnore sobre periodo_desde (índice único).
 * Si el período YA existe, NO lo toca (respeta cualquier valor cargado manualmente).
 * El down() no borra nada.
 *
 * Fuente: ANSES — Indicadores Monetarios de la Seguridad Social + Resoluciones ANSES mensuales
 * (https://www.argentina.gob.ar/trabajo/seguridadsocial/imss). Serie feb–jul 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // periodo_desde (YYYYMM) => [base_minima, tope_aportes (máxima), normativa, ipc_%]
        $topes = [
            ['periodo_desde' => '202602', 'base_minima' => 120996.78, 'tope_aportes' => 3932339.08, 'normativa' => 'Res. ANSES 21/2026',  'ipc_porcentaje' => 2.85],
            ['periodo_desde' => '202603', 'base_minima' => 124481.49, 'tope_aportes' => 4045590.45, 'normativa' => 'Res. ANSES 38/2026',  'ipc_porcentaje' => 2.88],
            ['periodo_desde' => '202604', 'base_minima' => 128091.45, 'tope_aportes' => 4162912.57, 'normativa' => 'Res. ANSES 74/2026',  'ipc_porcentaje' => 2.90],
            ['periodo_desde' => '202605', 'base_minima' => 132420.94, 'tope_aportes' => 4303619.01, 'normativa' => 'Res. ANSES 110/2026', 'ipc_porcentaje' => 3.38],
            ['periodo_desde' => '202606', 'base_minima' => 135837.40, 'tope_aportes' => 4414652.38, 'normativa' => 'Res. ANSES 139/2026', 'ipc_porcentaje' => 2.58],
            ['periodo_desde' => '202607', 'base_minima' => 138757.90, 'tope_aportes' => 4509567.41, 'normativa' => 'Res. ANSES (julio 2026)', 'ipc_porcentaje' => null],
        ];

        foreach ($topes as $t) {
            DB::table('lsd_topes')->insertOrIgnore(array_merge($t, [
                'observaciones' => 'Carga automática (migración seed).',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        // No se borra: son datos de referencia y no queremos perder topes cargados.
    }
};
