<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega al convenio (sue007s) el porcentaje de "Contribución tarea diferencial"
 * que se informa en el Registro 04 del Libro Sueldo Digital (LSD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sue007s', function (Blueprint $table) {
            $table->decimal('porc_tarea_dif', 5, 2)->nullable()->after('forzar50');
        });
    }

    public function down(): void
    {
        Schema::table('sue007s', function (Blueprint $table) {
            $table->dropColumn('porc_tarea_dif');
        });
    }
};
