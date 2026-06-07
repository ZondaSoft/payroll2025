<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->tinyInteger('tipo_liquidacion')->nullable()->after('periodo')
                ->comment('1=Mes, 2=Quincena, 3=Días, 4=Horas. Determina el código M/Q/D/H del Reg 01 pos 22.');
            $table->date('fecha_pago')->nullable()->after('tipo_liquidacion')
                ->comment('Fecha de pago efectiva de esta emisión. Si se completa, sobrescribe la fecha del período al armar el TXT (Reg 02).');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->dropColumn(['tipo_liquidacion', 'fecha_pago']);
        });
    }
};
