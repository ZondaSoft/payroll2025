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
        Schema::table('sue001s', function (Blueprint $table) {
            $table->boolean('sicoss_reduccion')->nullable()->after('baja_det');
            $table->boolean('sicoss_cob_scvo')->nullable()->after('sicoss_reduccion');
            $table->decimal('sicoss_porc_reduc', 5, 2)->nullable()->after('sicoss_cob_scvo');
            $table->boolean('sicoss_conyuge')->nullable()->after('sicoss_porc_reduc');
            $table->boolean('sicoss_hijos')->nullable()->after('sicoss_conyuge');
            $table->boolean('sicoss_adherentes')->nullable()->after('sicoss_hijos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sue001s', function (Blueprint $table) {
            //
        });
    }
};
