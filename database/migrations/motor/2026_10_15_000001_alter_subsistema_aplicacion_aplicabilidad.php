<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUBSISTEMA_APLICACION.aplicabilidad del DBML v2: GENERAL se evalúa para toda
 * relación; REGIMEN sólo cuando un régimen de la relación habilita la aplicación.
 *
 * La matriz S/N decide composición, no aplicabilidad (Etapa 3A, 2026-09-25). Sin
 * esta columna el motor no puede distinguir el aporte de RENATRE —que compone
 * REM_1 pero sólo corresponde al régimen rural— del aporte SIPA, y le exigiría
 * alícuota de RENATRE a cualquier trabajador.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        if (Schema::hasColumn('motor_subsistema_aplicacion', 'aplicabilidad')) {
            return;
        }

        Schema::table('motor_subsistema_aplicacion', function (Blueprint $t) {
            $t->string('aplicabilidad', 20)->default('GENERAL')->after('codigo_base')
                ->comment('GENERAL | REGIMEN. REGIMEN sólo se activa cuando RELACION_LABORAL_REGIMEN la habilita (DBML v2)');
        });

        // MEMORIA_CANONICA, actualización 2026-09-25: «RENATRE aporte/contribución son REGIMEN».
        Schema::getConnection()->table('motor_subsistema_aplicacion')
            ->whereIn('id_subsistema', Schema::getConnection()->table('motor_subsistemas')->where('codigo', 'RENATRE')->pluck('id_subsistema'))
            ->update(['aplicabilidad' => 'REGIMEN']);
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
