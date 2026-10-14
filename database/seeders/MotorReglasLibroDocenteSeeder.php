<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Las reglas de los libros docentes de agosto 2026 que el motor no contemplaba:
 * las ausencias no pagas, el día de la actividad y la proporcionalidad del básico.
 *
 * El criterio es el del libro docente v3, y lo que decide es **cómo se expone**:
 *
 * - Una **ausencia no paga** (inasistencia injustificada, suspensión, licencia sin
 *   goce) no achica el básico: se liquida el mes completo y la ausencia aparece
 *   como un haber remunerativo **negativo**, nunca mezclada con los aportes en la
 *   columna de descuentos. Por eso los tres conceptos son HABER con signo DEBITO.
 * - Su base diaria es BASE_HORA_NORMAL ÷ 30, que son los fijos del convenio. El
 *   premio asistencia entra en esa base con el importe que tenga: si la propia
 *   falta lo hizo perder, su fórmula ya devolvió cero y no se descuenta dos veces.
 * - El **básico se prorratea** sólo por días de vínculo reales (altas y bajas) y
 *   por los días de contingencia paga, que se pagan aparte. La versión 2 agrega ese
 *   factor sin tocar la versión 1, que sigue vigente hasta 07/2026: el 7019 de julio
 *   se liquida igual que antes.
 * - La **tolerancia horaria** se prorratea por los días efectivamente prestados
 *   (VAR(DIASEFE)), que descuentan también las ausencias no pagas. Es criterio
 *   operativo del libro, no normativo, y así cierra P-7.
 *
 * No es una migración a propósito: publica versiones y por lo tanto depende de que
 * los conceptos ya tengan su versión inicial. El orden es
 *
 *     MotorVersionesInicialesSeeder → MotorFormulasV5Seeder → este seeder
 *
 * y hay que reaplicarlo cada vez que se vuelve a sembrar desde el Excel, porque
 * MotorFormulasV5Seeder borra y reinserta todas las versiones de cada concepto.
 */
class MotorReglasLibroDocenteSeeder extends Seeder
{
    private string $ahora;

    public function run(): void
    {
        $this->ahora = now()->toDateTimeString();

        DB::transaction(function () {
            $this->variableDiasEfectivos();
            $this->novedades();
            $this->conceptos();
            $this->versionesNuevas();
            $this->proporcionalidadDelBasico();
            $this->toleranciaProrrateada();
            $this->asistenciaPierdeConSuspension();
            $this->redondeoDeLosAdicionales();
        });
    }

    private function variableDiasEfectivos(): void
    {
        DB::table('motor_variables')->updateOrInsert(['codigo' => 'DIASEFE'], [
            'descripcion' => 'Días efectivamente prestados del período',
            'unidad' => 'DIAS',
            'origen' => 'PERIODO',
            'activo' => true,
            'observaciones' => 'Vínculo menos todas las ausencias, pagas y no pagas. Distinto de DIASLIQ, '
                .'que son los días por los que se paga el básico: con una inasistencia injustificada el '
                .'básico va por 30 y la tolerancia horaria por 29.',
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);
    }

    private function novedades(): void
    {
        DB::table('motor_parametros')->updateOrInsert(['codigo' => 'SUSP_MAX'], [
            'descripcion' => 'Días de suspensión que no hacen perder el premio asistencia',
            'tipo_parametro' => 'ENTERO',
            'unidad' => 'DIAS',
            'es_tecnico' => false,
            'activo' => true,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);

        foreach ([
            ['DIAS_SUSP', 'Días de suspensión disciplinaria', 'DIAS'],
            ['DIAS_SIN_GOCE', 'Días de licencia sin goce de sueldo', 'DIAS'],
            ['DIA_ACTIVIDAD', 'Día de la actividad trabajado', 'DIAS'],
        ] as [$codigo, $descripcion, $unidad]) {
            DB::table('motor_tipos_novedad')->updateOrInsert(['codigo' => $codigo], [
                'descripcion' => $descripcion,
                'tipo_valor' => 'CANTIDAD',
                'unidad' => $unidad,
                'regla_agregacion' => 'SUMA',
                'activo' => true,
                'created_at' => $this->ahora,
                'updated_at' => $this->ahora,
            ]);
        }
    }

    private function conceptos(): void
    {
        foreach ([
            ['INASIS', 'Inasistencia injustificada', 'DEBITO',
                'Haber remunerativo negativo (criterio liquidatorio del libro docente v3): no se mezcla con los aportes.'],
            ['SUSPEN', 'Suspensión disciplinaria', 'DEBITO',
                'Haber remunerativo negativo. LCT arts. 218 a 220.'],
            ['SINGOCE', 'Licencia sin goce de sueldo', 'DEBITO',
                'Haber remunerativo negativo sobre mes completo. El premio asistencia se mantiene: el CCT no la enumera.'],
            ['DIAACT', 'Día de la actividad trabajado', 'CREDITO',
                'CCT 371/03 art. 14: recargo 100 %. El divisor operativo es el del convenio.'],
        ] as [$codigo, $nombre, $signo, $observaciones]) {
            DB::table('motor_conceptos')->updateOrInsert(['codigo_concepto' => $codigo], [
                'nombre_concepto' => $nombre,
                'alias_recibo' => $nombre,
                'tipo_concepto' => 'HABER',
                'naturaleza' => 'REMUNERATIVO',
                'signo' => $signo,
                'unidad_principal' => 'DIAS',
                'modo_calculo' => 'FORMULA',
                'requiere_novedad' => true,
                'editable_manual' => false,
                'repetible' => false,
                'permite_importe_cero' => false,
                'imprime_recibo' => true,
                'es_concepto_sistema' => false,
                'vigencia_desde' => '2026-01-01',
                'activo' => true,
                'estado_configuracion' => 'PUBLICADO',
                // tipo_variacion y ausentismo_evitable quedan NULL: el libro v5 no
                // declara su dominio de valores y el motor todavía no los consume.
                'observaciones_funcionales' => $observaciones,
                'created_at' => $this->ahora,
                'updated_at' => $this->ahora,
            ]);
        }
    }

    /**
     * Las cuatro fórmulas nuevas. Las tres ausencias comparten la misma: días por la
     * base diaria de los fijos. El día de la actividad usa el divisor de jornales del
     * convenio, igual que el feriado trabajado — el recargo del 100 % ya está en
     * pagar una jornada entera además del mensual.
     */
    private function versionesNuevas(): void
    {
        foreach ([
            ['INASIS', 'NOV(INASIS)', 'INASIS', 'CCT 371/03 art. 13 + criterio liquidatorio del libro docente v3'],
            ['SUSPEN', 'NOV(DIAS_SUSP)', 'DIAS_SUSP', 'LCT arts. 218 a 220'],
            ['SINGOCE', 'NOV(DIAS_SIN_GOCE)', 'DIAS_SIN_GOCE', 'LCT art. 158 y ss. (licencia sin goce)'],
            ['DIAACT', 'NOV(DIA_ACTIVIDAD)', 'DIA_ACTIVIDAD', 'CCT 371/03 art. 14; el divisor operativo es criterio del libro fuente'],
        ] as [$concepto, $cantidad, $novedad, $fuente]) {
            $this->publicar(
                concepto: $concepto,
                datos: [
                    'expr_cantidad' => $cantidad,
                    'expr_valor_unitario' => 'BASE(BASE_HORA_NORMAL) / '
                        .($concepto === 'DIAACT' ? 'VAR(DIVJOR)' : 'PARAM(DIVISOR_DIAS)'),
                    'expr_importe' => 'CANT * VALUNI',
                    'unidad_cantidad' => 'DIAS',
                    'regla_redondeo_version' => 'DECIMAL',
                    'fuente_normativa' => $fuente,
                    'motivo' => 'Alta de la regla en el motor (libro docente 08/2026).',
                ],
                novedades: [$novedad => 'REQUERIDA'],
                vigenciaDesde: '2026-01-01',
            );
        }
    }

    /**
     * Versión 2 del básico: el factor de días. Con 30 días liquidados sobre una base
     * de 30 el factor es 1 y el importe no cambia, así que la regla sólo se nota
     * donde tiene que notarse — altas, bajas y contingencias pagas.
     */
    private function proporcionalidadDelBasico(): void
    {
        foreach ([
            ['JORNADA_PACTADA', 'VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN) * VAR(DIASLIQ) / PARAM(DIVISOR_DIAS)'],
            ['LARGA_DISTANCIA', 'VAR(BASCAT) * VAR(DIASLIQ) / PARAM(DIVISOR_DIAS)'],
        ] as [$variante, $importe]) {
            $this->publicar(
                concepto: 'BASICO',
                datos: [
                    'expr_importe' => $importe,
                    'regla_redondeo_version' => 'DECIMAL',
                    'fuente_normativa' => 'Libro docente 08/2026: sólo las altas y bajas reales y las contingencias pagas proporcionan el básico',
                    'motivo' => 'Proporcionalidad por días de vínculo. Las ausencias no pagas NO achican el básico: se exponen como haber negativo.',
                ],
                variante: $variante,
                vigenciaDesde: '2026-08-01',
                cerrarAnterior: true,
            );
        }
    }

    /** Versión 2 de la tolerancia horaria: prorrateada por días efectivamente prestados. */
    private function toleranciaProrrateada(): void
    {
        $this->publicar(
            concepto: 'TOLHOR',
            datos: [
                'expr_cantidad' => 'VALCAT(TOLHOR) * VAR(DIASEFE) / PARAM(DIVISOR_DIAS)',
                'expr_valor_unitario' => 'VAR(BASCAT) / VAR(HMJORN)',
                'expr_importe' => 'CANT * VALUNI',
                'unidad_cantidad' => 'HORAS',
                'regla_redondeo_version' => 'DECIMAL',
                'fuente_normativa' => 'Res. 231/2007; el prorrateo por días efectivos es criterio operativo del libro docente (cierra P-7)',
                'motivo' => 'Prorrateo por días efectivamente prestados: a diferencia del básico, acá las ausencias no pagas también descuentan.',
            ],
            vigenciaDesde: '2026-08-01',
            cerrarAnterior: true,
        );
    }

    /**
     * La suspensión también hace perder el premio asistencia. El CCT enumera la
     * inasistencia injustificada; que la suspensión disciplinaria tenga el mismo
     * efecto es criterio operativo del libro fuente, y por eso va con su propio
     * parámetro: quien discuta el criterio cambia el tope, no la fórmula.
     */
    private function asistenciaPierdeConSuspension(): void
    {
        $this->publicar(
            concepto: 'ASIST',
            datos: [
                'expr_importe' => 'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX)'
                    .' O NOV(DIAS_SUSP) > PARAM(SUSP_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
                'regla_redondeo_version' => 'DECIMAL',
                'fuente_normativa' => 'CCT 371/03 art. 13; la pérdida por suspensión es criterio operativo del libro fuente',
                'motivo' => 'La suspensión disciplinaria también hace perder el premio (libro docente 08/2026, caso P05).',
            ],
            novedades: ['INASIS' => 'OPCIONAL', 'LLEGTAR' => 'OPCIONAL', 'DIAS_SUSP' => 'OPCIONAL'],
            vigenciaDesde: '2026-08-01',
            cerrarAnterior: true,
        );
    }

    /**
     * El libro docente redondea cada línea del recibo a dos decimales y arma las
     * bases con los importes ya redondeados: la base de la hora normal del testigo
     * da 1.944.560,31 y no 1.944.560,304. Es la política que cierra P-7, y acá se
     * aplica a los adicionales que todavía no la tenían.
     */
    private function redondeoDeLosAdicionales(): void
    {
        foreach ([
            ['ANTIG', 'BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)'],
            ['MANFON', 'CONC(BASICO) * PARAM(PORC_MANEJO_FONDOS)'],
        ] as [$concepto, $importe]) {
            $this->publicar(
                concepto: $concepto,
                datos: [
                    'expr_importe' => $importe,
                    'regla_redondeo_version' => 'DECIMAL',
                    'fuente_normativa' => 'Libro docente 08/2026: cada línea del recibo se redondea a dos decimales',
                    'motivo' => 'Política de redondeo del libro docente (P-7). La fórmula no cambia.',
                ],
                vigenciaDesde: '2026-08-01',
                cerrarAnterior: true,
            );
        }
    }

    private function snapshot(int $idConcepto, string $concepto): string
    {
        $heredado = DB::table('motor_concepto_versiones')->where('id_concepto', $idConcepto)
            ->orderBy('id_version')->value('snapshot');
        if ($heredado !== null) {
            return is_string($heredado) ? $heredado : json_encode($heredado);
        }

        $fila = DB::table('motor_conceptos')->where('id_concepto', $idConcepto)->first();

        return json_encode([
            'concepto' => [
                'codigo_concepto' => $concepto,
                'nombre_concepto' => $fila->nombre_concepto,
                'tipo_concepto' => $fila->tipo_concepto,
                'naturaleza' => $fila->naturaleza,
                'signo' => $fila->signo,
                'modo_calculo' => $fila->modo_calculo,
                'unidad_principal' => $fila->unidad_principal,
                'vigencia' => ['desde' => $fila->vigencia_desde, 'hasta' => $fila->vigencia_hasta],
                'activo' => (bool) $fila->activo,
                'estado_configuracion' => $fila->estado_configuracion,
            ],
            'incidencias_arca' => [],
            'bases_zonda' => [],
            'aplicabilidad' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>  $novedades
     */
    private function publicar(
        string $concepto,
        array $datos,
        array $novedades = [],
        string $variante = 'UNICA',
        string $vigenciaDesde = '2026-01-01',
        bool $cerrarAnterior = false,
    ): void {
        $idConcepto = DB::table('motor_conceptos')->where('codigo_concepto', $concepto)->value('id_concepto');
        if ($idConcepto === null) {
            return;
        }

        if ($cerrarAnterior) {
            // La versión anterior no se toca más allá de cerrarle la vigencia: lo
            // liquidado con ella tiene que poder reproducirse.
            DB::table('motor_concepto_versiones')
                ->where('id_concepto', $idConcepto)->where('variante', $variante)->whereNull('vigencia_hasta')
                ->update(['vigencia_hasta' => date('Y-m-d', strtotime($vigenciaDesde.' -1 day')), 'updated_at' => $this->ahora]);
        }

        $numero = 1 + (int) DB::table('motor_concepto_versiones')
            ->where('id_concepto', $idConcepto)->where('variante', $variante)->max('numero_version');

        $existente = DB::table('motor_concepto_versiones')
            ->where('id_concepto', $idConcepto)->where('variante', $variante)
            ->where('vigencia_desde', $vigenciaDesde)->value('id_version');
        if ($existente !== null) {
            return;
        }

        $id = DB::table('motor_concepto_versiones')->insertGetId([
            'id_concepto' => $idConcepto,
            'variante' => $variante,
            'metodo' => 'FORMULA',
            'estado' => 'PUBLICADA',
            'estado_validacion' => 'SIN_CONFIRMAR',
            'numero_version' => $numero,
            'vigencia_desde' => $vigenciaDesde,
            'esquema_snapshot' => 1,
            // El snapshot se hereda de la versión anterior del concepto: es la
            // definición congelada que el liquidador lee, y una regla nueva no la
            // cambia. Si el concepto es nuevo, se arma el mínimo que exige
            // Concepto::desdeDefinicion().
            'snapshot' => $this->snapshot($idConcepto, $concepto),
            'hash' => hash('sha256', $concepto.$variante.$vigenciaDesde.json_encode($datos)),
            'publicada_at' => $this->ahora,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ] + $datos, 'id_version');

        foreach ($novedades as $codigo => $obligatoriedad) {
            DB::table('motor_concepto_version_novedad')->insertOrIgnore([
                'id_version' => $id, 'codigo_novedad' => $codigo, 'obligatoriedad' => $obligatoriedad,
                'created_at' => $this->ahora, 'updated_at' => $this->ahora,
            ]);
        }
    }
}
