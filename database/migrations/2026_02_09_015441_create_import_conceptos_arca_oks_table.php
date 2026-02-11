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
        Schema::create('import_conceptos_arca_oks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->integer('tamanio_archivo')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('import_conceptos_arca_errs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->integer('tamanio_archivo')->nullable();
            $table->longText('detalle')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_conceptos_arca_oks');
        Schema::dropIfExists('import_conceptos_arca_errs');
    }
};
