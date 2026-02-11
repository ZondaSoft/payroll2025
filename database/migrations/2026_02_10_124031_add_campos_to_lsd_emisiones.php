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
            $table->string('archivo_txt', 255)->nullable()->after('archivo_xml');
            $table->string('hash_txt', 64)->nullable(); // sha256 hex
            $table->integer('cantidad_lineas')->default(0);
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
