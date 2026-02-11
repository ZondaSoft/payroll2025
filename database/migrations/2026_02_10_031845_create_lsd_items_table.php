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
        Schema::create('lsd_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lsd_emision_id')->constrained('lsd_emisiones')->cascadeOnDelete();
            $table->char('cuil', 11);
            $table->string('legajo')->nullable();
            $table->string('dependencia', 80)->nullable();
            $table->char('cbu', 22)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->integer('forma_pago')->nullable();
            $table->integer('codigo_concepto')->nullable();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->char('unidades', 1)->nullable();
            $table->decimal('importe', 13, 2)->nullable();
            $table->char('debito_credito', 1)->nullable();
            $table->char('periodo_ajuste', 6)->nullable();
            $table->tinyInteger('conyugue')->nullable();
            $table->tinyInteger('hijos')->nullable();
            $table->tinyInteger('marca_cct')->nullable();
            $table->tinyInteger('marca_scvo')->nullable();
            $table->tinyInteger('corr_reduccion')->nullable();
            $table->tinyInteger('tipo_empresa')->nullable();
            $table->tinyInteger('tipo_operacion')->nullable();
            $table->tinyInteger('situacion')->nullable();
            $table->tinyInteger('condicion')->nullable();
            $table->tinyInteger('actividad')->nullable();
            $table->tinyInteger('modalidad')->nullable();
            $table->tinyInteger('siniestro')->nullable();
            $table->tinyInteger('localidad')->nullable();
            $table->tinyInteger('revista1')->nullable();
            $table->tinyInteger('dia_revista1')->nullable();
            $table->tinyInteger('revista2')->nullable();
            $table->tinyInteger('dia_revista2')->nullable();
            $table->tinyInteger('revista3')->nullable();
            $table->tinyInteger('dia_revista3')->nullable();
            $table->smallInteger('dias_trabajados')->nullable();
            $table->integer('horas_trabajadas')->nullable();
            $table->decimal('porc_aporte_adic_ss', 6, 2)->nullable();
            $table->decimal('contr_tarea', 6, 2)->nullable();
            $table->integer('cod_os')->nullable();
            $table->tinyInteger('adherentes')->nullable();
            $table->decimal('aporte_adic_os', 13, 2)->nullable();
            $table->decimal('contrib_adic_os', 13, 2)->nullable();
            $table->decimal('base_calculo', 13, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lsd_items');
    }
};
