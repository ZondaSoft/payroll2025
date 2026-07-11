<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Registra con qué filtro de tipo de liquidación (sue090s.tipoliq) se generó la emisión:
     * '1' = Normal, '4' = SAC, '5' = Liq. Final, 'todas' = sin filtro (TXT global del mes).
     * No confundir con tipo_liquidacion (código M/Q/D/H del Reg 01). Las emisiones ya
     * generadas quedan en null (comportamiento histórico, sin filtro explícito).
     */
    public function up(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->string('tipoliq_filtro', 10)->nullable()->after('identificador_envio');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->dropColumn('tipoliq_filtro');
        });
    }
};
