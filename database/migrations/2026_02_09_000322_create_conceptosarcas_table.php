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
        Schema::create('conceptosarcas', function (Blueprint $table) {
            $table->id();
            
            // Campos principales ARCA
            $table->integer('codigo_afip')->nullable();  // 6 dígitos
            $table->string('descripcion', 80)->nullable();
            $table->integer('codigo_contribuyente')->nullable();
            $table->string('descripcion_contribuyente', 80)->nullable();
            
            // Campos de aportaciones y contribuciones (decimal 9,3)
            $table->decimal('marca_repetible', 9, 3)->nullable();
            $table->decimal('aportes_sipa', 9, 3)->nullable();
            $table->decimal('contribuciones_sipa', 9, 3)->nullable();
            $table->decimal('aportes_inssjyp', 9, 3)->nullable();
            $table->decimal('contribuciones_inssjyp', 9, 3)->nullable();
            $table->decimal('aportes_obra_social', 9, 3)->nullable();
            $table->decimal('contribuciones_obra_social', 9, 3)->nullable();
            $table->decimal('aportes_fsr', 9, 3)->nullable();
            $table->decimal('contribuciones_fsr', 9, 3)->nullable();
            $table->decimal('aportes_renatea', 9, 3)->nullable();
            $table->decimal('contribuciones_renatea', 9, 3)->nullable();
            $table->decimal('contribuciones_aaff', 9, 3)->nullable();
            $table->decimal('contribuciones_fne', 9, 3)->nullable();
            $table->decimal('contribuciones_lrt', 9, 3)->nullable();
            $table->decimal('aportes_diferenciales', 9, 3)->nullable();
            $table->decimal('aportes_especiales', 9, 3)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptosarcas');
    }
};
