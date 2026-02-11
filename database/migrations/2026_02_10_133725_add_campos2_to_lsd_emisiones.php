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
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->string('periodo', 6)->nullable()->after('fecha_emision'); // formato YYYYMM
            $table->string('cuit_empresa', 11)->nullable()->after('id_empresa');
            $table->string('afip_transaccion_id', 100)->nullable();

            // Keys and Indexes
            $table->index(['id_empresa', 'periodo']);
            $table->index('estado');
            $table->foreign('usuario_id')->references('id')->on('users');
            $table->foreign('id_empresa')->references('id')->on('sue086s');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            //
        });
    }
};
