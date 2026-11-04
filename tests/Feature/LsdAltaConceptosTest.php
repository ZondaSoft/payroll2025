<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\UsaEsquemaApp;
use Tests\TestCase;

/**
 * El alta de los conceptos que la liquidación usa y el catálogo (`sue102s`) no tiene.
 *
 * Es la misma operación que corre desde el modal y desde la generación automática
 * del LSD, así que lo que se verifica acá vale para los dos caminos: de dónde sale
 * el tipo, de dónde el código ARCA, y qué queda registrado cuando alguno de los dos
 * no se puede determinar — porque un concepto creado sin código ARCA está dado de
 * alta pero no se puede informar en el Reg 03.
 */
final class LsdAltaConceptosTest extends TestCase
{
    use UsaEsquemaApp;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Los rangos reales del cliente: 1-199 haberes, 200-499 descuentos y de 500
        // en adelante no remunerativo.
        DB::table('sue089s')->insert([
            ['desde' => 1, 'hasta' => 199, 'tiporem' => 'H'],
            ['desde' => 200, 'hasta' => 499, 'tiporem' => 'D'],
            ['desde' => 500, 'hasta' => 99999999, 'tiporem' => 'NR'],
        ]);

        $this->usuario = User::query()->create([
            'name' => 'Test', 'email' => 'lsd@example.com', 'password' => bcrypt('secret'),
        ]);
    }

    /** @param list<array<string, mixed>> $conceptos */
    private function darDeAlta(array $conceptos, ?int $idEmpresa = null): array
    {
        $payload = ['conceptos' => $conceptos];
        if ($idEmpresa !== null) {
            $payload['id_empresa'] = $idEmpresa;
        }

        return $this->actingAs($this->usuario)
            ->postJson(route('lsd.generar.conceptos'), $payload)
            ->assertOk()->json();
    }

    public function test_el_tipo_sale_del_rango_que_contiene_al_codigo(): void
    {
        $r = $this->darDeAlta([
            ['concepto' => '101', 'descripcion' => 'Sueldo básico'],
            ['concepto' => '250', 'descripcion' => 'Jubilación'],
            ['concepto' => '522', 'descripcion' => 'Vianda Ayuda Alimentaria'],
        ]);

        self::assertSame(3, $r['creados']);
        self::assertSame(['H', 'D', 'NR'], DB::table('sue102s')->orderBy('codigo')->pluck('tipo')->all());
    }

    public function test_el_codigo_arca_sale_de_la_parametrizacion_de_la_empresa(): void
    {
        // El mismo código propio puede mapear a códigos AFIP distintos según la
        // empresa: el alta tiene que tomar el de la empresa que está generando.
        DB::table('conceptosarcas')->insert([
            ['id_empresa' => 1, 'codigo_contribuyente' => 511, 'codigo_afip' => 520012, 'descripcion' => 'Vacaciones no gozadas'],
            ['id_empresa' => 5, 'codigo_contribuyente' => 511, 'codigo_afip' => 550000, 'descripcion' => 'Importes no remunerativos especiales'],
        ]);

        $this->darDeAlta([['concepto' => '511', 'descripcion' => 'Vacaciones No Goz.']], idEmpresa: 5);

        self::assertSame('550000', DB::table('sue102s')->where('codigo', 511)->value('concepto_arca'));
    }

    public function test_sin_parametrizacion_el_concepto_queda_sin_codigo_arca_y_se_informa(): void
    {
        $r = $this->darDeAlta([['concepto' => '523', 'descripcion' => 'Asignación Vianda Complementaria']]);

        self::assertSame(1, $r['creados']);
        self::assertSame(1, $r['sin_arca']);
        self::assertNull(DB::table('sue102s')->where('codigo', 523)->value('concepto_arca'));
        // El detalle es lo que viaja al resumen de la generación: tiene que decir que
        // el concepto quedó sin código, no sólo que se creó.
        self::assertSame([[
            'concepto' => '523',
            'descripcion' => 'Asignación Vianda Complementaria',
            'tipo' => 'NR',
            'concepto_arca' => null,
        ]], $r['detalle']);
    }

    public function test_un_codigo_fuera_de_todo_rango_queda_sin_tipo_y_se_informa(): void
    {
        DB::table('sue089s')->delete();
        DB::table('sue089s')->insert([['desde' => 1, 'hasta' => 199, 'tiporem' => 'H']]);

        $r = $this->darDeAlta([['concepto' => '900', 'descripcion' => 'Concepto raro']]);

        self::assertSame(1, $r['sin_tipo']);
        self::assertSame('', DB::table('sue102s')->where('codigo', 900)->value('tipo'));
        self::assertSame('', $r['detalle'][0]['tipo']);
    }

    public function test_no_duplica_un_concepto_que_ya_existe(): void
    {
        DB::table('sue102s')->insert(['codigo' => 101, 'detalle' => 'Ya estaba', 'tipo' => 'H']);

        $r = $this->darDeAlta([
            ['concepto' => '101', 'descripcion' => 'Sueldo básico'],
            ['concepto' => '102', 'descripcion' => 'Antigüedad'],
        ]);

        self::assertSame(1, $r['creados']);
        self::assertSame(1, $r['omitidos']);
        self::assertSame('Ya estaba', DB::table('sue102s')->where('codigo', 101)->value('detalle'));
        self::assertCount(1, $r['detalle'], 'el detalle sólo lista lo que se creó');
    }

    public function test_normaliza_las_descripciones_con_saltos_de_linea_del_basedat(): void
    {
        // El BASEDAT las trae partidas ("Vacaciones\nNo Goz."): si se guardan así, el
        // recibo y el catálogo muestran el nombre cortado.
        $this->darDeAlta([['concepto' => '744', 'descripcion' => "Vacaciones\nNo Goz."]]);

        self::assertSame('Vacaciones No Goz.', DB::table('sue102s')->where('codigo', 744)->value('detalle'));
    }

    public function test_un_concepto_sin_descripcion_queda_con_su_codigo_como_nombre(): void
    {
        $this->darDeAlta([['concepto' => '777', 'descripcion' => '   ']]);

        self::assertSame('Concepto 777', DB::table('sue102s')->where('codigo', 777)->value('detalle'));
    }
}
