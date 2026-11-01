<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Segundo parámetro del ABM /config: si la generación del LSD pregunta o no por los
        // empleados activos sin liquidación del período. Arranca en true para no cambiarle el
        // comportamiento a nadie: hoy el diálogo se muestra siempre.
        Schema::table('sue_config', function (Blueprint $table) {
            $table->boolean('lsd_preguntar_activos')->default(true)->after('lsd_no_conceptos')
                ->comment('Preguntar por los empleados activos sin liquidación al generar el LSD');
        });
    }

    public function down(): void
    {
        Schema::table('sue_config', function (Blueprint $table) {
            $table->dropColumn('lsd_preguntar_activos');
        });
    }
};
