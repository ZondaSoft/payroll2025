<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo: importa a los catálogos ARCA del motor los catálogos SICOSS
 * que payroll2025 ya tiene cargados y validados contra ARCA real:
 *   sicoss01s → motor_actividades_arca · sicoss05s → motor_condiciones_arca
 *   sicoss08s → motor_modalidades_arca · sicoss12s → motor_situaciones_revista_arca
 *   sicoss_zonas → motor_localidades_arca (clave = numero de 2 caracteres)
 *
 * IDEMPOTENTE con insertOrIgnore (no updateOrInsert): no pisa las filas que ya
 * sembró la migración 07 desde el Excel ni las editadas por el usuario.
 * Debe correr ANTES de la primera sincronización de relaciones laborales.
 */
class MotorCatalogosSicossSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now()->toDateTimeString();
        $reporte = [];

        $reporte['motor_actividades_arca'] = $this->importar('sicoss01s', 'motor_actividades_arca', fn ($r) => [
            'codigo_actividad_arca' => (string) $r->codigo, 'descripcion' => mb_substr((string) $r->detalle, 0, 100), 'estado' => 'VIGENTE',
        ], $ahora);
        $reporte['motor_condiciones_arca'] = $this->importar('sicoss05s', 'motor_condiciones_arca', fn ($r) => [
            'codigo_condicion' => (string) $r->codigo, 'descripcion' => mb_substr((string) $r->detalle, 0, 255), 'activo' => 1,
        ], $ahora);
        $reporte['motor_modalidades_arca'] = $this->importar('sicoss08s', 'motor_modalidades_arca', fn ($r) => [
            'codigo_modalidad' => (string) $r->codigo, 'descripcion' => mb_substr((string) $r->detalle, 0, 100), 'activo' => 1,
        ], $ahora);
        $reporte['motor_situaciones_revista_arca'] = $this->importar('sicoss12s', 'motor_situaciones_revista_arca', fn ($r) => [
            'codigo_situacion' => (string) $r->codigo, 'descripcion' => mb_substr((string) $r->detalle, 0, 100), 'activo' => 1,
        ], $ahora);
        $reporte['motor_localidades_arca'] = $this->importar('sicoss_zonas', 'motor_localidades_arca', fn ($r) => [
            'codigo_localidad_arca' => str_pad(trim((string) $r->numero), 2, '0', STR_PAD_LEFT),
            'descripcion' => mb_substr((string) $r->detalle, 0, 100), 'estado' => 'VIGENTE',
        ], $ahora, orden: 'numero');

        foreach ($reporte as $tabla => $n) {
            $this->command?->info(sprintf('%-32s %5d filas (total ahora %d)', $tabla, $n, DB::table($tabla)->count()));
        }
    }

    /** @return int filas leídas del origen */
    private function importar(string $origen, string $destino, \Closure $mapa, string $ahora, string $orden = 'codigo'): int
    {
        if (! Schema::hasTable($origen)) {
            $this->command?->warn("Tabla legacy {$origen} inexistente: se omite.");

            return 0;
        }
        $n = 0;
        foreach (DB::table($origen)->orderBy($orden)->cursor() as $r) {
            $fila = $mapa($r);
            $clave = array_key_first($fila);
            if ($fila[$clave] === '' || $fila[$clave] === null) {
                continue;
            }
            DB::table($destino)->insertOrIgnore($fila + ['created_at' => $ahora, 'updated_at' => $ahora]);
            $n++;
        }

        return $n;
    }
}
