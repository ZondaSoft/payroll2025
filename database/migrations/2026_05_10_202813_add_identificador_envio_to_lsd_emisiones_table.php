<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->char('identificador_envio', 2)->default('SJ')->after('periodo')
                ->comment('Identificador del envío del TXT (Reg 01 pos 14-15). SJ = SyJ + DJ F931 (normal). RE = Solo rectifica DJ F931.');
        });

        // Backfill: todas las emisiones previas fueron generadas con 'SJ'.
        DB::table('lsd_emisiones')->update(['identificador_envio' => 'SJ']);
    }

    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->dropColumn('identificador_envio');
        });
    }
};
