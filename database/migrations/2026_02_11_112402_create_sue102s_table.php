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
        Schema::create('sue102s', function (Blueprint $table) {
            $table->id();

            $table->integer('codigo')
                ->comment('Código del concepto');

            $table->string('detalle', 250)
                ->comment('Descripción del concepto');

            $table->unsignedTinyInteger('tipo')->default(1)->comment(
                "1:HABER,
        2:DESCUENTO,
        3:ASIGNACIONES,
        4:NO_REMUNERATIVO,
        5:GANANCIAS,
        6:DEVOLUCION DE GANANCIA,
        7:REDONDEO,
        8:APORTES,
        9:AUXILIARES"
            );

            $table->text('formula')->nullable()->comment('Fórmula de cálculo');
            $table->decimal('porcentaje', 8, 4)->nullable()->comment('Porcentaje si aplica');
            $table->decimal('importe_fijo', 12, 2)->nullable()->comment('Importe fijo si aplica');

            $table->boolean('imponible')->default(true)->comment('Si es imponible para aportes');
            $table->boolean('afecta_sac')->default(true)->comment('Si afecta al SAC');
            $table->boolean('afecta_vacaciones')->default(true)->comment('Si afecta vacaciones');
            $table->boolean('imprime_recibo')->default(true)->comment('Si se imprime en recibo');

            $table->integer('orden_impresion')->nullable()->comment('Orden de impresión en recibo');
            $table->boolean('activo')->default(true)->comment('Si el concepto está activo');

            $table->string('cuenta_contable', 50)->nullable()->comment('Cuenta contable asociada');
            $table->text('observaciones')->nullable()->comment('Observaciones adicionales');

            $table->boolean('sicoss_afecta')->default(false)->comment('Si el concepto afecta a Sicoss');
            $table->boolean('gcias_afecta')->default(false)->comment('Si el concepto afecta a Ganancias');

            // Si el código interno debe ser único:
            $table->unique('codigo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sue102s');
    }
};
