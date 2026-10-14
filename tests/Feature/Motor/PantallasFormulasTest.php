<?php

namespace Tests\Feature\Motor;

use App\Models\User;
use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorReglasLibroDocenteSeeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\EscenarioLibro;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/**
 * Las pantallas que cierran el círculo: el editor de fórmulas con validación
 * dimensional en vivo, la matriz de perfiles y los valores de variables por
 * convenio.
 *
 * Lo que se verifica no es que la página renderice: es que la pantalla **no deje
 * guardar lo que el motor después no puede ejecutar**. Un perfil que incluye un
 * concepto sin variante, una fórmula cuyo importe no devuelve pesos, dos valores
 * vigentes en el mismo ámbito — todo eso se rechaza acá, antes de liquidar.
 */
final class PantallasFormulasTest extends TestCase
{
    use UsaEsquemaMotor;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
        $this->seed(MotorReglasLibroDocenteSeeder::class);
        EscenarioLibro::sembrar();
        $this->usuario = User::query()->create([
            'name' => 'Test', 'email' => 'pantallas@example.com', 'password' => bcrypt('secret'),
        ]);
    }

    /** @param array<string, mixed> $datos */
    private function analizar(array $datos): array
    {
        return $this->actingAs($this->usuario)
            ->postJson(route('motor.formulas.analizar'), $datos + ['codigo_concepto' => 'HE50'])
            ->assertOk()->json();
    }

    public function test_el_editor_valida_la_formula_del_libro_y_dice_su_unidad(): void
    {
        $r = $this->analizar([
            'expr_cantidad' => 'NOV(HE_50)',
            'expr_valor_unitario' => 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
            'expr_importe' => 'CANT * VALUNI * 1.5',
            'unidad_cantidad' => 'HORAS',
            'novedades' => ['HE_50' => 'REQUERIDA'],
            'justificaciones' => ['1.5' => 'LCT art. 201 — recargo del 50 %'],
            'modo' => 'PUBLICACION',
        ]);

        self::assertTrue($r['publica'], json_encode($r['errores'], JSON_UNESCAPED_UNICODE));
        self::assertSame('ARS', $r['unidad_importe']);
        self::assertSame('HORAS', $r['unidades']['cantidad']);
        self::assertSame('ARS/HORAS', $r['unidades']['valor']);
    }

    public function test_el_editor_distingue_lo_que_entra_al_grafo_de_lo_que_solo_se_lee(): void
    {
        $r = $this->analizar([
            'expr_importe' => 'BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE) + CONC(BASICO) * PARAM(PORC_ASISTENCIA)',
            'codigo_concepto' => 'ANTIG',
        ]);

        $aristas = array_values(array_map(fn ($d) => $d['tipo'], array_filter($r['dependencias'], fn ($d) => $d['arista'])));
        $lecturas = array_values(array_map(fn ($d) => $d['tipo'], array_filter($r['dependencias'], fn ($d) => ! $d['arista'])));

        self::assertEqualsCanonicalizing(['BASE', 'MODULO', 'CONCEPTO'], $aristas);
        self::assertSame(['PARAMETRO'], $lecturas);
    }

    public function test_el_editor_marca_el_error_dimensional_del_contrato(): void
    {
        // Comparar días contra un escalar: el 0 no es comodín dimensional fuera de SI().
        $r = $this->analizar([
            'expr_condicion' => 'NOV(INASIS) > 0',
            'expr_importe' => 'CONC(BASICO) * PARAM(PORC_ASISTENCIA)',
            'novedades' => ['INASIS' => 'OPCIONAL'],
            'codigo_concepto' => 'ASIST',
        ]);

        self::assertFalse($r['publica']);
        self::assertContains('DSL_UNIDAD_INCOMPATIBLE', $r['codigos']);
        self::assertStringContainsString('comparar DIAS con ESCALAR', $r['errores'][0]['mensaje']);
    }

    public function test_el_editor_avisa_la_novedad_usada_sin_declarar(): void
    {
        $r = $this->analizar([
            'expr_cantidad' => 'NOV(HE_50)',
            'expr_valor_unitario' => 'BASE(BASE_HORA_NORMAL) / VAR(HMJORN)',
            'expr_importe' => 'CANT * VALUNI',
            'unidad_cantidad' => 'HORAS',
        ]);

        self::assertContains('DSL_NOVEDAD_NO_DECLARADA', $r['codigos']);
    }

    public function test_en_edicion_la_unidad_sin_declarar_es_advertencia_y_al_publicar_bloquea(): void
    {
        // Un parámetro cuya unidad el catálogo no declara: no se puede afirmar que el
        // importe sea ARS, pero tampoco que esté mal. Editando es advertencia.
        DB::table('motor_parametros')->insert([
            'codigo' => 'SIN_UNIDAD_TEST', 'descripcion' => 'Prueba', 'tipo_parametro' => 'PORCENTAJE',
            'unidad' => null, 'es_tecnico' => false, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $datos = ['expr_importe' => 'PARAM(SIN_UNIDAD_TEST) * VAR(BASCAT)', 'codigo_concepto' => 'ADTIT'];

        $edicion = $this->analizar($datos + ['modo' => 'EDICION']);
        $publicacion = $this->analizar($datos + ['modo' => 'PUBLICACION']);

        self::assertTrue($edicion['publica']);
        self::assertFalse($publicacion['publica']);
    }

    public function test_el_catalogo_del_editor_lista_las_referencias_disponibles(): void
    {
        $c = $this->actingAs($this->usuario)->getJson(route('motor.formulas.catalogo'))->assertOk()->json();

        self::assertContains('PORC_ASISTENCIA', array_column($c['PARAM'], 'codigo'));
        self::assertContains('BASE_HORA_NORMAL', array_column($c['BASE'], 'codigo'));
        self::assertContains('ANTIGUEDAD.PORCENTAJE', array_column($c['MOD'], 'codigo'));
        self::assertSame('ARS/KM', collect($c['PARAM'])->firstWhere('codigo', 'TARIFA_KM')['unidad']);
        self::assertEqualsCanonicalizing(['SI', 'MIN', 'MAX', 'ABS', 'REDONDEAR'], $c['funciones']);
    }

    public function test_publicar_una_version_cierra_la_anterior_y_no_la_edita(): void
    {
        $antes = DB::table('motor_concepto_versiones as v')->join('motor_conceptos as c', 'c.id_concepto', '=', 'v.id_concepto')
            ->where('c.codigo_concepto', 'MANFON')->whereNull('v.vigencia_hasta')->first(['v.id_version', 'v.expr_importe']);

        $this->actingAs($this->usuario)->post(route('motor.formulas.publicar'), [
            'codigo_concepto' => 'MANFON',
            'variante' => 'UNICA',
            'metodo' => 'FORMULA',
            'expr_importe' => 'CONC(BASICO) * PARAM(PORC_MANEJO_FONDOS)',
            'regla_redondeo' => 'DECIMAL',
            'vigencia_desde' => '2026-12-01',
            'motivo' => 'Paritaria 12/2026.',
        ])->assertRedirect();

        // La anterior quedó cerrada el día previo, con su expresión intacta: lo
        // liquidado con ella tiene que poder reproducirse.
        $vieja = DB::table('motor_concepto_versiones')->where('id_version', $antes->id_version)->first();
        self::assertSame('2026-11-30', $vieja->vigencia_hasta);
        self::assertSame($antes->expr_importe, $vieja->expr_importe);

        $nueva = DB::table('motor_concepto_versiones as v')->join('motor_conceptos as c', 'c.id_concepto', '=', 'v.id_concepto')
            ->where('c.codigo_concepto', 'MANFON')->where('v.vigencia_desde', '2026-12-01')->first();
        self::assertSame('PUBLICADA', $nueva->estado);
        self::assertSame('Paritaria 12/2026.', $nueva->motivo);
    }

    public function test_no_publica_una_version_que_no_pasa_la_validacion(): void
    {
        $this->actingAs($this->usuario)->post(route('motor.formulas.publicar'), [
            'codigo_concepto' => 'MANFON',
            'variante' => 'UNICA',
            'metodo' => 'FORMULA',
            // Devuelve horas, no pesos.
            'expr_importe' => 'VAR(HMJORN)',
            'regla_redondeo' => 'DECIMAL',
            'vigencia_desde' => '2026-12-01',
            'motivo' => 'Prueba.',
        ])->assertSessionHasErrors('expr_importe');

        self::assertSame(0, DB::table('motor_concepto_versiones')->where('vigencia_desde', '2026-12-01')->count());
    }

    public function test_el_perfil_no_acepta_un_concepto_que_aplica_sin_variante(): void
    {
        $id = DB::table('motor_perfil')->where('codigo_perfil', 'CCT_371_03')->value('id_perfil');

        $this->actingAs($this->usuario)->postJson(route('motor.perfiles.reglas.store', $id), [
            'codigo_concepto' => 'DIAACT', 'aplica' => true, 'variante' => '',
            'desde' => '2026-01-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'necesita variante'));
    }

    public function test_el_perfil_no_acepta_una_exclusion_sin_motivo(): void
    {
        $id = DB::table('motor_perfil')->where('codigo_perfil', 'CCT_371_03')->value('id_perfil');

        $this->actingAs($this->usuario)->postJson(route('motor.perfiles.reglas.store', $id), [
            'codigo_concepto' => 'DIAACT', 'aplica' => false, 'motivo' => '',
            'desde' => '2026-01-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'necesita motivo'));
    }

    public function test_el_perfil_rechaza_una_variante_que_el_concepto_no_publica(): void
    {
        $id = DB::table('motor_perfil')->where('codigo_perfil', 'CCT_371_03')->value('id_perfil');

        $this->actingAs($this->usuario)->postJson(route('motor.perfiles.reglas.store', $id), [
            'codigo_concepto' => 'DIAACT', 'aplica' => true, 'variante' => 'MEDIA_JORNADA',
            'desde' => '2026-01-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'no tiene publicada la variante'));
    }

    public function test_el_perfil_guarda_la_regla_con_su_variante(): void
    {
        $id = DB::table('motor_perfil')->where('codigo_perfil', 'CCT_371_03')->value('id_perfil');

        $this->actingAs($this->usuario)->postJson(route('motor.perfiles.reglas.store', $id), [
            'codigo_concepto' => 'DIAACT', 'aplica' => true, 'variante' => 'UNICA',
            'motivo' => 'Día de la Estación de Servicio (CCT art. 14).', 'desde' => '2026-01-01', 'estado' => 'CONFIRMADO',
        ])->assertOk()->assertJsonPath('success', true);

        $fila = DB::table('motor_perfil_concepto')->where('id_perfil', $id)->where('codigo_concepto', 'DIAACT')->first();
        self::assertSame('UNICA', $fila->variante);
        self::assertSame(1, (int) $fila->aplica);
    }

    public function test_el_perfil_rechaza_dos_reglas_del_mismo_concepto_con_vigencia_superpuesta(): void
    {
        $id = DB::table('motor_perfil')->where('codigo_perfil', 'CCT_371_03')->value('id_perfil');

        $this->actingAs($this->usuario)->postJson(route('motor.perfiles.reglas.store', $id), [
            'codigo_concepto' => 'ASIST', 'aplica' => true, 'variante' => 'UNICA',
            'desde' => '2026-06-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'RT-007'));
    }

    public function test_el_indice_de_perfiles_muestra_las_ambiguedades_antes_de_liquidar(): void
    {
        $ahora = now()->toDateTimeString();
        $otro = DB::table('motor_perfil')->insertGetId([
            'codigo_perfil' => 'CCT_371_03_BIS', 'nombre' => 'Duplicado', 'nivel' => 'CONVENIO',
            'orden_precedencia' => 4, 'id_convenio' => EscenarioLibro::CONVENIO_371,
            'vigencia_desde' => '2026-01-01', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
        ], 'id_perfil');
        DB::table('motor_perfil_concepto')->insert([
            'id_perfil' => $otro, 'codigo_concepto' => 'ASIST', 'aplica' => false, 'motivo' => 'contradice al otro perfil',
            'vigencia_desde' => '2026-01-01', 'estado_validacion' => 'CONFIRMADO', 'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        $this->actingAs($this->usuario)->get(route('motor.perfiles.index'))->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('ambiguos.0.concepto', 'ASIST')
                ->where('ambiguos.0.codigo', 'PERFIL_AMBIGUO')
                ->where('ambiguos.0.nivel', 'CONVENIO'));
    }

    public function test_cargar_el_divisor_de_un_convenio_nuevo(): void
    {
        // Es lo que bloqueaba poner un convenio en marcha: hasta ahora DIVJOR y
        // HMJORN sólo se cargaban por seeder.
        $this->actingAs($this->usuario)->postJson(route('motor.variables.valores.store', 'DIVJOR'), [
            'alcance' => 'CONVENIO', 'id_convenio' => 9, 'valor' => '30',
            'desde' => '2026-01-01', 'estado' => 'CONFIRMADO', 'fuente' => 'CCT de Comercio',
        ])->assertOk()->assertJsonPath('success', true);

        self::assertSame('30.000000', DB::table('motor_valores_variable')
            ->where('codigo', 'DIVJOR')->where('id_convenio', 9)->value('valor'));
    }

    public function test_no_se_puede_cargar_un_valor_para_una_variable_que_resuelve_el_contexto(): void
    {
        $this->actingAs($this->usuario)->postJson(route('motor.variables.valores.store', 'BASCAT'), [
            'alcance' => 'CONVENIO', 'id_convenio' => 9, 'valor' => '1000',
            'desde' => '2026-01-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'no se leería nunca'));
    }

    public function test_el_valor_de_variable_rechaza_la_vigencia_superpuesta(): void
    {
        $this->actingAs($this->usuario)->postJson(route('motor.variables.valores.store', 'DIVJOR'), [
            'alcance' => 'CONVENIO', 'id_convenio' => EscenarioLibro::CONVENIO_371, 'valor' => '26',
            'desde' => '2026-06-01', 'estado' => 'CONFIRMADO',
        ])->assertStatus(422)->assertJsonPath('message', fn (string $m) => str_contains($m, 'RT-007'));
    }

    public function test_el_codigo_de_una_variable_respeta_el_largo_del_dsl(): void
    {
        $this->actingAs($this->usuario)->post(route('motor.variables.store'), [
            'codigo' => 'DIVISOR_LARGO', 'descripcion' => 'Prueba', 'origen' => 'CONVENIO', 'unidad' => 'DIAS',
        ])->assertSessionHasErrors('codigo');
    }

    public function test_el_indice_de_formulas_resume_lo_que_valida_y_lo_que_no(): void
    {
        $this->actingAs($this->usuario)->get(route('motor.formulas.index'))->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('resumen.con_problemas', 0)
                ->where('resumen.total', fn (int $t) => $t >= 20)
                ->etc());
    }
}
