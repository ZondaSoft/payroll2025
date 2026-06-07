<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Completar lsd_topes para reflejar la tabla oficial de Bases Imponibles SIPA:
        // Período (periodo_desde) · Mínima (base_minima) · Máxima (tope_aportes) · Resolución (normativa) · % IPC.
        Schema::table('lsd_topes', function (Blueprint $table) {
            $table->decimal('base_minima', 13, 2)->nullable()->after('tope_aportes')
                ->comment('Base imponible mínima de aportes del período. Ej: 132420.94');
            $table->decimal('ipc_porcentaje', 6, 2)->nullable()->after('normativa')
                ->comment('Variación IPC aplicada al período. Ej: 3.38 = +3,38%');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_topes', function (Blueprint $table) {
            $table->dropColumn(['base_minima', 'ipc_porcentaje']);
        });
    }
};
