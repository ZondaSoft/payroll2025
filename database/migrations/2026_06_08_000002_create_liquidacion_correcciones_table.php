<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Histórico de correcciones automáticas sobre la liquidación (sue090s). Cualquier proceso que
     * modifique un importe liquidado (ajuste de aportes del LSD, liquidación individual/global, etc.)
     * debe registrar acá el antes/después con su motivo y origen, para trazabilidad.
     */
    public function up(): void
    {
        Schema::create('liquidacion_correcciones', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 6)->index();          // YYYYMM
            $table->integer('tipoliq')->nullable();
            $table->string('legajo', 20)->nullable()->index();
            $table->string('cuil', 11)->nullable()->index();
            $table->string('concepto', 20)->nullable();      // código de concepto interno
            $table->string('concepto_arca', 10)->nullable();
            $table->unsignedBigInteger('sue090_id')->nullable(); // fila afectada (si aplica)
            $table->decimal('importe_anterior', 15, 2)->nullable();
            $table->decimal('importe_nuevo', 15, 2)->nullable();
            $table->string('motivo', 255)->nullable();       // descripción legible
            $table->string('origen', 60)->nullable();        // 'ajuste_aportes_lsd', 'liquidacion_individual', etc.
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidacion_correcciones');
    }
};
