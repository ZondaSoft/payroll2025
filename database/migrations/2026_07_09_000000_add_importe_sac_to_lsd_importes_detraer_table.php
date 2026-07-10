<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el importe a detraer para MESES CON SAC (aguinaldo). La detracción del Dto 814/01
 * (Ley 27.430 art. 4) se informa incrementada ×1,5 en los meses en que se devenga SAC.
 * Ej.: mensual 7.003,68 → mes con SAC 10.505,52.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lsd_importes_detraer', 'importe_sac')) {
            return;
        }

        Schema::table('lsd_importes_detraer', function (Blueprint $table) {
            $table->decimal('importe_sac', 13, 2)->nullable()->after('importe')
                ->comment('Importe a detraer para meses con SAC (×1,5 del mensual)');
        });

        // Backfill: por defecto, 1,5 × el importe mensual ya cargado.
        DB::statement('UPDATE lsd_importes_detraer SET importe_sac = ROUND(importe * 1.5, 2) WHERE importe_sac IS NULL');
    }

    public function down(): void
    {
        if (Schema::hasColumn('lsd_importes_detraer', 'importe_sac')) {
            Schema::table('lsd_importes_detraer', function (Blueprint $table) {
                $table->dropColumn('importe_sac');
            });
        }
    }
};
