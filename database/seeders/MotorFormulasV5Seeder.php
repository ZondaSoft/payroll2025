<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga en las versiones de concepto las fórmulas DSL del Excel funcional v5,
 * con su variante, sus novedades declaradas y sus literales justificados.
 *
 * Va en un seeder y no en la migración porque necesita el snapshot del concepto,
 * que publica MotorVersionesInicialesSeeder: el snapshot congela la definición
 * del concepto y lo consume el liquidador; la fórmula es lo que agrega el modelo
 * v2 encima, en columnas propias.
 *
 *     php artisan db:seed --class=MotorVersionesInicialesSeeder   # primero
 *     php artisan db:seed --class=MotorFormulasV5Seeder
 */
class MotorFormulasV5Seeder extends Seeder
{
    private const DIR = __DIR__.'/motor/datos/v5';

    public function run(): void
    {
        $ahora = now()->toDateTimeString();

        // RT-006: una versión usada en una liquidación no se reescribe.
        $usadas = (int) DB::table('motor_concepto_versiones')->where('usada_en_liquidacion', 1)->count();
        if ($usadas > 0) {
            throw new RuntimeException(
                "Hay {$usadas} versión(es) de concepto usadas en liquidaciones: este seeder las reescribiría. ".
                'Resolver a mano antes de continuar (RT-006).'
            );
        }

        $filas = $this->datos('concepto_version');
        $novedades = $this->datos('concepto_version_novedad');
        $literales = $this->datos('concepto_version_literal');

        $porConcepto = [];
        foreach ($filas as $f) {
            $porConcepto[$f['codigo_concepto']][] = $f;
        }

        DB::transaction(function () use ($porConcepto, $novedades, $literales, $ahora) {
            foreach ($porConcepto as $codigo => $versiones) {
                $idConcepto = DB::table('motor_conceptos')->where('codigo_concepto', $codigo)->value('id_concepto');
                if ($idConcepto === null) {
                    throw new RuntimeException("La versión de fórmula referencia el concepto inexistente {$codigo}.");
                }

                $snapshotBase = DB::table('motor_concepto_versiones')->where('id_concepto', $idConcepto)
                    ->orderBy('id_version')->value('snapshot');
                if ($snapshotBase === null) {
                    throw new RuntimeException(
                        "El concepto {$codigo} no tiene versión publicada de la que tomar el snapshot. ".
                        'Correr antes: php artisan db:seed --class=MotorVersionesInicialesSeeder'
                    );
                }

                $ids = DB::table('motor_concepto_versiones')->where('id_concepto', $idConcepto)->pluck('id_version');
                DB::table('motor_concepto_version_novedad')->whereIn('id_version', $ids)->delete();
                DB::table('motor_concepto_version_literal')->whereIn('id_version', $ids)->delete();
                DB::table('motor_concepto_versiones')->where('id_concepto', $idConcepto)->delete();

                // Ordenadas por variante y vigencia: así se numeran y se cierra la
                // anterior cuando entra una nueva. El libro deja vigencia_hasta en NULL
                // y dos versiones PUBLICADA solapadas bloquearían (ASIST: v1 de enero,
                // v2 de julio con el redondeo propio del concepto).
                usort($versiones, fn ($a, $b) => [$a['variante'], $a['vigencia_desde']] <=> [$b['variante'], $b['vigencia_desde']]);
                $numeroPorVariante = [];
                foreach ($versiones as $i => $f) {
                    $variante = $f['variante'];
                    $numeroPorVariante[$variante] = ($numeroPorVariante[$variante] ?? 0) + 1;
                    $hasta = $f['vigencia_hasta'];
                    $siguiente = $versiones[$i + 1] ?? null;
                    if ($hasta === null && $siguiente !== null && $siguiente['variante'] === $variante) {
                        $hasta = date('Y-m-d', strtotime($siguiente['vigencia_desde'].' -1 day'));
                    }

                    // La identidad de la versión es el concepto congelado MÁS su fórmula:
                    // dos variantes comparten snapshot y se distinguen por la expresión.
                    $formula = [
                        'variante' => $variante, 'metodo' => $f['metodo'],
                        'expr_condicion' => $f['expr_condicion'], 'expr_cantidad' => $f['expr_cantidad'],
                        'expr_valor_unitario' => $f['expr_valor_unitario'], 'expr_importe' => $f['expr_importe'],
                        'unidad_cantidad' => $f['unidad_cantidad'], 'regla_redondeo' => $f['regla_redondeo'],
                        'decimales' => $f['decimales'], 'codigo_modulo' => $f['codigo_modulo'],
                    ];

                    $idVersion = DB::table('motor_concepto_versiones')->insertGetId([
                        'id_concepto' => $idConcepto,
                        'variante' => $variante,
                        'metodo' => $f['metodo'],
                        'numero_version' => $numeroPorVariante[$variante],
                        'vigencia_desde' => $f['vigencia_desde'],
                        'vigencia_hasta' => $hasta,
                        'expr_condicion' => $f['expr_condicion'],
                        'expr_cantidad' => $f['expr_cantidad'],
                        'expr_valor_unitario' => $f['expr_valor_unitario'],
                        'expr_importe' => $f['expr_importe'],
                        'unidad_cantidad' => $f['unidad_cantidad'],
                        'regla_redondeo_version' => $f['regla_redondeo'],
                        'decimales' => $f['decimales'] !== null ? (int) $f['decimales'] : null,
                        'codigo_modulo' => $f['codigo_modulo'],
                        'estado' => $f['estado'],
                        'estado_validacion' => $f['estado_validacion'],
                        'fuente_normativa' => $f['fuente_normativa'],
                        'esquema_snapshot' => 1,
                        'snapshot' => $snapshotBase,
                        'hash' => hash('sha256', $snapshotBase.'|'.json_encode($formula, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                        'motivo' => $f['motivo_version'] ?? 'Excel funcional v5 (DBML v2)',
                        'tipo_cambio' => 'ALTA',
                        'publicada_at' => $f['estado'] === 'PUBLICADA' ? $ahora : null,
                        'usada_en_liquidacion' => false,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);

                    // Novedades declaradas: una fórmula que usa NOV() sin declararlo no publica.
                    foreach ($novedades as $n) {
                        if ((string) $n['id_concepto_version'] !== (string) $f['id_concepto_version']) {
                            continue;
                        }
                        DB::table('motor_concepto_version_novedad')->updateOrInsert(
                            ['id_version' => $idVersion, 'codigo_novedad' => $n['codigo_novedad']],
                            ['obligatoriedad' => $n['obligatoriedad'], 'observaciones' => $n['observaciones'] ?? null,
                                'created_at' => $ahora, 'updated_at' => $ahora]
                        );
                    }
                    foreach ($literales as $l) {
                        if ((string) $l['id_concepto_version'] !== (string) $f['id_concepto_version']) {
                            continue;
                        }
                        DB::table('motor_concepto_version_literal')->updateOrInsert(
                            ['id_version' => $idVersion, 'literal' => $l['literal']],
                            ['justificacion' => $l['justificacion'], 'created_at' => $ahora, 'updated_at' => $ahora]
                        );
                    }
                }
            }
        });

        $conFormula = (int) DB::table('motor_concepto_versiones')->whereNotNull('expr_importe')->count();
        $variantes = (int) DB::table('motor_concepto_versiones')->where('variante', '!=', 'UNICA')->count();
        $this->command?->info("Fórmulas v5 cargadas: {$conFormula} versiones con expresión, {$variantes} con variante propia, "
            .DB::table('motor_concepto_version_novedad')->count().' novedades y '
            .DB::table('motor_concepto_version_literal')->count().' literales declarados.');
    }

    /** @return list<array<string, mixed>> */
    private function datos(string $hoja): array
    {
        $ruta = self::DIR."/{$hoja}.php";
        if (! is_file($ruta)) {
            throw new RuntimeException("Falta el archivo de datos {$ruta}. Regenerar con generar_datos_v5.py.");
        }

        return require $ruta;
    }
}
