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
        Schema::table('sue102s', function (Blueprint $table) {
            $table->string('concepto_arca', 6)->nullable()->after('sicoss_afecta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sue102s', function (Blueprint $table) {
            $table->dropColumn('concepto_arca');
        });
    }
};
