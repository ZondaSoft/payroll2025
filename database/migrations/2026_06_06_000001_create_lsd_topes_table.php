<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tope máximo de la base imponible para APORTES (jubilación SIPA, INSSJyP y obra social).
        // Lo publica ANSeS por período (movilidad). Se aplica a las bases imponibles 1, 4 y 5 del LSD.
        // Las contribuciones (BI 2/3/8) no tienen tope. Mismo patrón que lsd_importes_detraer.
        Schema::create('lsd_topes', function (Blueprint $table) {
            $table->id();
            $table->char('periodo_desde', 6)->unique()->comment('Período YYYYMM desde el que aplica el tope');
            $table->decimal('tope_aportes', 13, 2)->comment('Tope máximo de la base imponible de aportes (BI 1/4/5). Ej: 3932339.09');
            $table->string('normativa', 255)->nullable()->comment('Referencia legal: ej. Resolución ANSeS de movilidad');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
        });

        // Sin registro semilla: el valor oficial lo carga el usuario por período desde el ABM.
        // Mientras no haya tope cargado para un período, el LSD no aplica tope (comportamiento previo).
    }

    public function down(): void
    {
        Schema::dropIfExists('lsd_topes');
    }
};
