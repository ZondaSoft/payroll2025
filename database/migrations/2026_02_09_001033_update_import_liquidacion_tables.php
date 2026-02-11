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
        // Aumentar tamaño del campo detalle en import_liquidacion_errs
        Schema::table('import_liquidacion_errs', function (Blueprint $table) {
            $table->longText('detalle')->nullable()->change();
        });

        // Hacer nullable el campo descripcion en import_liquidacion_oks
        Schema::table('import_liquidacion_oks', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_liquidacion_errs', function (Blueprint $table) {
            $table->string('detalle', 255)->nullable()->change();
        });

        Schema::table('import_liquidacion_oks', function (Blueprint $table) {
            $table->string('descripcion', 255)->change();
        });
    }
};
