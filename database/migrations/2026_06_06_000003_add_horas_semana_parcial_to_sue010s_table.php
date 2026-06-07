<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sue010s', function (Blueprint $table) {
            $table->integer('horas_semana')->nullable()->after('detalle')
                ->comment('Cantidad de horas semanales de la jornada');
            $table->boolean('parcial')->default(false)->after('horas_semana')
                ->comment('Indica si la jornada es a tiempo parcial');
        });
    }

    public function down(): void
    {
        Schema::table('sue010s', function (Blueprint $table) {
            $table->dropColumn(['horas_semana', 'parcial']);
        });
    }
};
