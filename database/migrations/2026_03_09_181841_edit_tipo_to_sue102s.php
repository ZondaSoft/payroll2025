<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sue102s', function (Blueprint $table) {
            $table->string('tipo', 2)->default('H')
                ->change()
                ->comment('Tipo de concepto: H=HABER, D=DESCUENTO, AS=ASIGNACIONES, NR=NO_REMUNERATIVO, GC=GANANCIAS, DG=DEVOLUCIÓN DE GANANCIA, R=REDONDEO, AP=APORTES, AU=AUXILIARES');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sue102s', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
