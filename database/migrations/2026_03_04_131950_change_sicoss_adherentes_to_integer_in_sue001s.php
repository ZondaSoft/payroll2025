<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero convertir NULLs y valores no numéricos a 0
        DB::statement('UPDATE sue001s SET sicoss_adherentes = 0 WHERE sicoss_adherentes IS NULL');
        DB::statement('ALTER TABLE sue001s MODIFY sicoss_adherentes SMALLINT NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE sue001s MODIFY sicoss_adherentes TINYINT(1) NOT NULL DEFAULT 0');
    }
};
