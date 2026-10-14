<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Support\Facades\DB;

/**
 * La versión 2 de ASIST (vigente desde 07/2026) condiciona el premio asistencia a
 * `NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX)`, pero el
 * libro v5 no declara esos dos parámetros: la fórmula se publicó referenciando
 * configuración que no existe.
 *
 * Se crean acá para que la referencia resuelva, **sin valor**: el tope de
 * inasistencias y de llegadas tarde es normativo del CCT y tiene que cargarlo
 * quien lo pueda confirmar. Hasta entonces el motor bloquea ASIST con
 * PARAMETRO_SIN_VALOR, que es el comportamiento correcto — no cobrar el premio
 * «porque no había dato» sería decidir por el convenio.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $ahora = now()->toDateTimeString();
        foreach ([
            ['INASIS_MAX', 'Tope de días de inasistencia que no hacen perder el premio asistencia', 'ENTERO', 'DIAS'],
            ['LLEGTAR_MAX', 'Tope de llegadas tarde que no hacen perder el premio asistencia', 'ENTERO', 'UNIDADES'],
        ] as [$codigo, $descripcion, $tipo, $unidad]) {
            DB::table('motor_parametros')->updateOrInsert(['codigo' => $codigo], [
                'descripcion' => $descripcion,
                'tipo_parametro' => $tipo,
                'unidad' => $unidad,
                'es_tecnico' => false,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
