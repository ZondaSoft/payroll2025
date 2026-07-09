<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Idempotente: si una corrida previa quedó a medias (ej. corte de conexión) y la columna
        // ya existe, no se vuelve a agregar (evita "Duplicate column name").
        Schema::table('sue010s', function (Blueprint $table) {
            if (!Schema::hasColumn('sue010s', 'horas_semana')) {
                $table->integer('horas_semana')->nullable()->after('detalle')
                    ->comment('Cantidad de horas semanales de la jornada');
            }
            if (!Schema::hasColumn('sue010s', 'parcial')) {
                $table->boolean('parcial')->default(false)->after('horas_semana')
                    ->comment('Indica si la jornada es a tiempo parcial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sue010s', function (Blueprint $table) {
            foreach (['horas_semana', 'parcial'] as $col) {
                if (Schema::hasColumn('sue010s', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
