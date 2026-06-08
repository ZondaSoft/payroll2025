<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Almacena los legajos que se ignoraron al generar la emisión (cuando el usuario eligió
     * "Ignorar y continuar" ante inconsistencias SICOSS). Estructura: array de
     * { legajo, cuil, nombre, motivos: [{ campo, detalle }] }. Las emisiones ya generadas
     * quedan en null (no se reprocesan).
     */
    public function up(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->json('legajos_ignorados')->nullable()->after('cantidad_empleados');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->dropColumn('legajos_ignorados');
        });
    }
};
