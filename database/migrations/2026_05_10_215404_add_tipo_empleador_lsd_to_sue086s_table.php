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
        Schema::table('sue086s', function (Blueprint $table) {
            $table->char('tipo_empleador_lsd', 1)->default('1')->after('actividad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sue086s', function (Blueprint $table) {
            $table->dropColumn('tipo_empleador_lsd');
        });
    }
};
