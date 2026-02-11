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
        Schema::table('import_conceptos_arca_oks', function (Blueprint $table) {
            $table->integer('codigo_afip')->nullable()->after('tamanio_archivo');  // 6 dígitos
            $table->integer('codigo_contribuyente')->nullable()->after('descripcion');  // 6 dígitos
            $table->string('descripcion_contribuyente', 80)->nullable()->after('codigo_contribuyente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_conceptos_arca_oks', function (Blueprint $table) {
            $table->dropColumn('codigo_afip');
            $table->dropColumn('codigo_contribuyente');
            $table->dropColumn('descripcion_contribuyente');
        });
    }
};
