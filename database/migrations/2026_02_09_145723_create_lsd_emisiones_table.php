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
        Schema::create('lsd_emisiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->nullable();
            $table->integer('numero_emision')->nullable();      // correlativo interno por empresa y período
            $table->date('fecha_emision')->nullable();
            $table->date('periodo_desde')->nullable();
            $table->date('periodo_hasta')->nullable();
            $table->integer('cantidad_empleados')->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);
            $table->enum('estado', ['borrador', 'generado', 'enviado', 'confirmado', 'rechazado'])->default('borrador');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('fecha_generacion')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->string('archivo_pdf', 255)->nullable();
            $table->string('archivo_xml', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lsd_emisiones');
    }
};
