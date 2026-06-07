<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_liquidacion_oks', function (Blueprint $table) {
            $table->string('situacion_ant', 10)->nullable()->after('detalle');
            $table->string('situacion_imp', 10)->nullable()->after('situacion_ant');
            $table->string('obra_social_ant', 20)->nullable()->after('situacion_imp');
            $table->string('obra_social_imp', 20)->nullable()->after('obra_social_ant');
            $table->string('condicion_ant', 10)->nullable()->after('obra_social_imp');
            $table->string('condicion_imp', 10)->nullable()->after('condicion_ant');
            $table->string('actividad_ant', 10)->nullable()->after('condicion_imp');
            $table->string('actividad_imp', 10)->nullable()->after('actividad_ant');
            $table->string('modalidad_ant', 10)->nullable()->after('actividad_imp');
            $table->string('modalidad_imp', 10)->nullable()->after('modalidad_ant');
            $table->string('siniestro_ant', 10)->nullable()->after('modalidad_imp');
            $table->string('siniestro_imp', 10)->nullable()->after('siniestro_ant');
            $table->string('localidad_ant', 10)->nullable()->after('siniestro_imp');
            $table->string('localidad_imp', 10)->nullable()->after('localidad_ant');
        });
    }

    public function down(): void
    {
        Schema::table('import_liquidacion_oks', function (Blueprint $table) {
            $table->dropColumn([
                'situacion_ant', 'situacion_imp',
                'obra_social_ant', 'obra_social_imp',
                'condicion_ant', 'condicion_imp',
                'actividad_ant', 'actividad_imp',
                'modalidad_ant', 'modalidad_imp',
                'siniestro_ant', 'siniestro_imp',
                'localidad_ant', 'localidad_imp',
            ]);
        });
    }
};
