<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Un encabezado de período por (periodo, tipoliq): el mismo mes admite Normal + SAC +
     * Liq. Final, pero no dos filas del mismo tipo (duplicaba el selector de períodos de la
     * liquidación individual). Refuerza a nivel base la validación del ABM de Períodos.
     * Prerequisito: los duplicados existentes fueron depurados antes de esta migración.
     */
    public function up(): void
    {
        Schema::table('sue100s', function (Blueprint $table) {
            $table->unique(['periodo', 'tipoliq'], 'sue100s_periodo_tipoliq_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sue100s', function (Blueprint $table) {
            $table->dropUnique('sue100s_periodo_tipoliq_unique');
        });
    }
};
