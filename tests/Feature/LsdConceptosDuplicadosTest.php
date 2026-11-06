<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\UsaEsquemaApp;
use Tests\TestCase;

/**
 * Primer control de la generación del LSD: el mismo concepto más de una vez en un mismo
 * recibo (legajo + período + tipoliq). Es lo que deja una liquidación recargada sin borrar
 * la anterior, y duplicaría importes, bases y aportes del TXT.
 */
final class LsdConceptosDuplicadosTest extends TestCase
{
    use UsaEsquemaApp;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::query()->create([
            'name' => 'Test', 'email' => 'lsd-dup@example.com', 'password' => bcrypt('secret'),
        ]);

        DB::table('sue086s')->insert(['id' => 1, 'codigo' => 1, 'detalle' => 'EMPRESA', 'cuit' => '30708835540']);
        DB::table('sue100s')->insert(['periodo' => '202610', 'tipoliq' => 1]);
        DB::table('sue001s')->insert([
            ['id' => 15403, 'codigo' => 279, 'detalle' => 'CARRIZO', 'nombres' => 'MATIAS', 'grupo_emp' => 1, 'baja' => null],
            ['id' => 15405, 'codigo' => 284, 'detalle' => 'BARBOZA', 'nombres' => 'DELFOR', 'grupo_emp' => 1, 'baja' => null],
            ['id' => 99999, 'codigo' => 900, 'detalle' => 'OTRA', 'nombres' => 'EMPRESA', 'grupo_emp' => 2, 'baja' => null],
        ]);
        DB::table('sue102s')->insert([
            ['codigo' => 1, 'detalle' => 'Sueldo Básico', 'tipo' => 'H', 'concepto_arca' => '110000'],
            ['codigo' => 200, 'detalle' => 'Jubilación', 'tipo' => 'D', 'concepto_arca' => '810001'],
        ]);
    }

    private function liquidar(int $legajo, string $concepto, float $importe, string $tipoliq = '1'): void
    {
        DB::table('sue090s')->insert([
            'periodo' => '202610', 'tipoliq' => $tipoliq, 'legajo' => $legajo,
            'concepto' => $concepto, 'cantidad' => 0, 'importe' => $importe,
        ]);
    }

    private function generar(array $extra = [])
    {
        return $this->actingAs($this->usuario)->postJson(route('lsd.generar.emision'), [
            'id_empresa' => 1,
            'periodo_id' => '202610',
            'identificador_envio' => 'SJ',
            'tipo_liquidacion' => 1,
            'fecha_pago' => '2026-11-05',
            'tipos_liq' => '1',
        ] + $extra);
    }

    public function test_frena_la_generacion_con_el_detalle_de_cada_concepto_repetido(): void
    {
        $this->liquidar(279, '1', 3464528.82);
        $this->liquidar(279, '1', 3464528.82);
        $this->liquidar(279, '200', 322539.25);
        $this->liquidar(279, '200', 398882.75);
        $this->liquidar(284, '1', 1000000);

        $r = $this->generar()->assertStatus(422)->assertJsonPath('tipo_error', 'conceptos_duplicados');

        $dup = $r->json('duplicados');
        self::assertCount(2, $dup);
        self::assertSame([279, 279], array_column($dup, 'legajo'));
        self::assertSame(15403, $dup[0]['legajo_id']);
        self::assertSame(['1', '200'], array_column($dup, 'concepto'));
        self::assertSame([3464528.82, 3464528.82], $dup[0]['importes']);
        self::assertFalse($dup[0]['distintos'], 'copia exacta');
        self::assertTrue($dup[1]['distintos'], 'la repetición trae otro importe');
    }

    public function test_es_el_primer_control_antes_que_los_conceptos_huerfanos(): void
    {
        // El 777 no existe en sue102s: igual tiene que ganar el control de duplicados.
        $this->liquidar(279, '777', 10);
        $this->liquidar(279, '777', 10);

        $this->generar()->assertStatus(422)->assertJsonPath('tipo_error', 'conceptos_duplicados');
    }

    public function test_el_mismo_concepto_en_otro_tipo_de_liquidacion_no_es_duplicado(): void
    {
        // Normal y SAC son recibos distintos: el básico puede estar en los dos.
        $this->liquidar(279, '1', 100, '1');
        $this->liquidar(279, '1', 50, '4');

        $r = $this->generar(['tipos_liq' => 'todas']);
        self::assertNotSame('conceptos_duplicados', $r->json('tipo_error'));
    }

    public function test_ignora_legajos_de_otra_empresa_y_dados_de_baja(): void
    {
        $this->liquidar(900, '1', 100);
        $this->liquidar(900, '1', 100);
        DB::table('sue001s')->where('codigo', 284)->update(['baja' => '2026-09-30']);
        $this->liquidar(284, '1', 100);
        $this->liquidar(284, '1', 100);

        self::assertNotSame('conceptos_duplicados', $this->generar()->json('tipo_error'));
    }

    public function test_con_ignorar_sigue_con_los_demas_controles(): void
    {
        $this->liquidar(279, '777', 10);
        $this->liquidar(279, '777', 10);

        // Pasa el control de duplicados y lo frena el siguiente (concepto 777 sin catálogo).
        $r = $this->generar(['ignorar_conceptos_duplicados' => true]);
        self::assertNotSame('conceptos_duplicados', $r->json('tipo_error'));
    }
}
