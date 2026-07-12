<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tipo de liquidación (sue090s.tipoliq, Mapa A: 1=Normal, 4=SAC, 5=Liq. Final, etc.)
     * de la fila de sue090s que originó cada item. Permite detallar por línea en el
     * detalle por concepto cuando la emisión engloba varios tipos (filtro "Todas").
     * Items de emisiones previas quedan en null (se resuelve por fallback).
     */
    public function up(): void
    {
        Schema::table('lsd_items', function (Blueprint $table) {
            $table->tinyInteger('tipoliq')->nullable()->after('codigo_concepto');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_items', function (Blueprint $table) {
            $table->dropColumn('tipoliq');
        });
    }
};
