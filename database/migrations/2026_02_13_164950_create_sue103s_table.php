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
        Schema::create('sue103s', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('tipo')->default(1)->comment("1:HABER,
                2:DESCUENTO,
                3:ASIGNACIONES,
                4:NO_REMUNERATIVO,
                5:GANANCIAS,
                6:DEVOLUCION DE GANANCIA,
                7:REDONDEO,
                8:APORTES,
                9:AUXILIARES");

            $table->unsignedInteger('desde')->nullable();
            $table->unsignedInteger('hasta')->nullable();

            // (opcional) índice útil para búsquedas por rango
            $table->index(['tipo', 'desde', 'hasta']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sue103s');
    }
};
