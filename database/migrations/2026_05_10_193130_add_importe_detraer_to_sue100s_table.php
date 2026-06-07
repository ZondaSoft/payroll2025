<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sue100s', function (Blueprint $table) {
            $table->decimal('importe_detraer', 13, 2)->default(0)->after('fecha_pago')
                ->comment('Importe a detraer Ley 27.430 vigente para este período. Si está en 0 se toma del maestro lsd_importes_detraer.');
        });

        // Backfill: poblar los períodos existentes con el valor histórico hardcoded.
        DB::table('sue100s')->update(['importe_detraer' => 7003.68]);
    }

    public function down(): void
    {
        Schema::table('sue100s', function (Blueprint $table) {
            $table->dropColumn('importe_detraer');
        });
    }
};
