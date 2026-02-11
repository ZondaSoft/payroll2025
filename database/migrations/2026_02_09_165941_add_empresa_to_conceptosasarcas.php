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
        Schema::table('conceptosarcas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_empresa')->nullable()->after('id');
            $table->foreign('id_empresa')
                ->references('id')
                ->on('sue086s')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conceptosarcas', function (Blueprint $table) {
            $table->dropForeignKey(['id_empresa']);
            $table->dropColumn('id_empresa');
        });
    }
};
