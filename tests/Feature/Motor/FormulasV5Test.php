<?php

namespace Tests\Feature\Motor;

use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/** Las fórmulas DSL del Excel v5 cargadas sobre las versiones publicadas. */
final class FormulasV5Test extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
    }

    private function version(string $concepto, string $variante = 'UNICA'): ?object
    {
        return DB::table('motor_concepto_versiones')
            ->join('motor_conceptos', 'motor_conceptos.id_concepto', '=', 'motor_concepto_versiones.id_concepto')
            ->where('codigo_concepto', $concepto)->where('variante', $variante)
            ->orderByDesc('motor_concepto_versiones.vigencia_desde')
            ->first(['motor_concepto_versiones.*']);
    }

    public function test_basico_queda_con_sus_dos_variantes(): void
    {
        self::assertSame(['JORNADA_PACTADA', 'LARGA_DISTANCIA'], DB::table('motor_concepto_versiones')
            ->join('motor_conceptos', 'motor_conceptos.id_concepto', '=', 'motor_concepto_versiones.id_concepto')
            ->where('codigo_concepto', 'BASICO')->orderBy('variante')->pluck('variante')->all());

        // En larga distancia el básico es el de escala, sin HSPACT: no es un dato faltante
        self::assertSame('VAR(BASCAT)', $this->version('BASICO', 'LARGA_DISTANCIA')->expr_importe);
        self::assertSame('VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)', $this->version('BASICO', 'JORNADA_PACTADA')->expr_importe);
    }

    public function test_las_formulas_verificadas_quedan_cargadas(): void
    {
        self::assertSame('BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)', $this->version('ANTIG')->expr_importe);
        self::assertSame('BASE(BASE_ACUERDO_5_6_2) * PARAM(PORC_ACUERDO_EMPRESA_SINDICATO_5_6_2)', $this->version('ACDSIND562')->expr_importe);
        // FERTRAB en días (DIVJOR) y FERTRABHS en horas (HMJORN): dos conceptos, no dos unidades
        self::assertSame('BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)', $this->version('FERTRAB')->expr_valor_unitario);
        self::assertSame('BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', $this->version('FERTRABHS')->expr_valor_unitario);
        // APJUB se resuelve por módulo, no por fórmula
        self::assertSame('MODULO', $this->version('APJUB')->metodo);
        self::assertSame('SEG_SOCIAL.APORTE_JUBILATORIO', $this->version('APJUB')->codigo_modulo);
    }

    public function test_asist_versiona_la_regla_de_asistencia_sin_solaparse(): void
    {
        $versiones = DB::table('motor_concepto_versiones')
            ->join('motor_conceptos', 'motor_conceptos.id_concepto', '=', 'motor_concepto_versiones.id_concepto')
            ->where('codigo_concepto', 'ASIST')->orderBy('motor_concepto_versiones.vigencia_desde')
            // con select * el join traería la vigencia del CONCEPTO, no la de la versión
            ->get(['motor_concepto_versiones.*']);

        self::assertCount(2, $versiones);
        // La v1 cierra el día anterior a la v2 — el propio libro la trae cerrada:
        // dos versiones PUBLICADA vigentes a la vez bloquearían
        self::assertSame('2026-06-30', substr((string) $versiones[0]->vigencia_hasta, 0, 10));
        self::assertNull($versiones[1]->vigencia_hasta);
        // La v2 incorpora la pérdida del adicional y su redondeo propio
        self::assertStringContainsString('NOV(INASIS)', (string) $versiones[1]->expr_importe);
        self::assertSame('DECIMAL', $versiones[1]->regla_redondeo_version);
    }

    public function test_novedades_y_literales_quedan_declarados(): void
    {
        self::assertSame(15, DB::table('motor_concepto_version_novedad')->count());
        self::assertSame(3, DB::table('motor_concepto_version_literal')->count());
        // El 1.5 de HE50 se justifica normativamente (DSL §15)
        $literal = DB::table('motor_concepto_version_literal')
            ->join('motor_concepto_versiones', 'motor_concepto_versiones.id_version', '=', 'motor_concepto_version_literal.id_version')
            ->join('motor_conceptos', 'motor_conceptos.id_concepto', '=', 'motor_concepto_versiones.id_concepto')
            ->where('codigo_concepto', 'HE50')->first();
        self::assertSame('1.5', $literal->literal);
        self::assertStringContainsString('201', (string) $literal->justificacion);
    }
}
