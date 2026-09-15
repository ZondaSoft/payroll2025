<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\SincronizarRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Dominio\Enums\EstadoRelacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

final class SincronizarRelacionesLaboralesTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        DB::table('sue086s')->insert([['id' => 6, 'codigo' => '05', 'detalle' => 'BLEXA S.A.'], ['id' => 1, 'codigo' => '01', 'detalle' => 'PETROANDINA SRL']]);
        DB::table('sue006s')->insert([['id' => 300, 'codigo' => '0106', 'detalle' => 'Operario'], ['id' => 301, 'codigo' => '0107', 'detalle' => 'Encargado']]);
        DB::table('sicoss_zonas')->insert(['id' => 1, 'codigo' => 61, 'numero' => '31', 'detalle' => 'Salta']);
        DB::table('motor_localidades_arca')->insert(['codigo_localidad_arca' => '31', 'descripcion' => 'Salta', 'estado' => 'VIGENTE']);
        DB::table('sue001s')->insert([
            'id' => 15579, 'codigo' => 7000, 'cuil' => '27300000005', 'detalle' => 'ALEMAN', 'nombres' => 'CECILIA', 'alta' => '2022-07-01', 'baja' => null,
            'cod_categ' => '0106', 'convenio' => '3', 'grupo_emp' => '05', 'sicoss_condi' => 1, 'sicoss_modal' => 8, 'sicoss_zona' => 61,
        ]);
    }

    public function test_crea_la_relacion_con_sus_hijas_y_es_idempotente(): void
    {
        $caso = app(SincronizarRelacionesLaborales::class);
        $r1 = $caso->ejecutar('05', Periodo::de('2026-07'));
        $r2 = $caso->ejecutar('05', Periodo::de('2026-07'));

        self::assertSame([1, 0, 0], [$r1->creadas, $r1->actualizadas, $r1->sinCambio]);
        self::assertSame([0, 0, 1], [$r2->creadas, $r2->actualizadas, $r2->sinCambio]);

        $rl = app(RepositorioRelacionesLaborales::class)->todas(6)[0];
        self::assertSame('7000', $rl->legajo);
        self::assertSame(15579, $rl->idLegajo);
        self::assertSame('0106', $rl->categoriaVigenteA(new DateTimeImmutable('2026-07-31'))?->codigoCategoria);
        self::assertSame('31', $rl->encuadreArca?->codigoLocalidad, 'la zona interna 61 se traduce al número ARCA 31');
        self::assertSame('GENERAL', $rl->regimenes[0]->codigoRegimen);
        self::assertSame(1, DB::table('motor_if_relacion_laboral_origen')->where('clave_origen', '15579')->count());
    }

    public function test_cambio_de_categoria_se_historiza(): void
    {
        $caso = app(SincronizarRelacionesLaborales::class);
        $caso->ejecutar('05', Periodo::de('2026-07'));
        DB::table('sue001s')->where('id', 15579)->update(['cod_categ' => '0107']);

        $r = $caso->ejecutar('05', Periodo::de('2026-08'), new DateTimeImmutable('2026-08-01'));

        self::assertSame(1, $r->actualizadas);
        $rl = app(RepositorioRelacionesLaborales::class)->todas(6)[0];
        self::assertCount(2, $rl->categorias);
        self::assertSame('0106', $rl->categoriaVigenteA(new DateTimeImmutable('2026-07-31'))?->codigoCategoria);
        self::assertSame('0107', $rl->categoriaVigenteA(new DateTimeImmutable('2026-08-15'))?->codigoCategoria);
    }

    public function test_cambio_de_empresa_marca_inconsistente_y_no_mueve(): void
    {
        $caso = app(SincronizarRelacionesLaborales::class);
        $caso->ejecutar(null, Periodo::de('2026-07'));
        DB::table('sue001s')->where('id', 15579)->update(['grupo_emp' => '01']);

        $r = $caso->ejecutar(null, Periodo::de('2026-07'));

        self::assertNotEmpty($r->conflictos);
        $rl = app(RepositorioRelacionesLaborales::class)->todas()[0];
        self::assertSame(EstadoRelacion::InconsistenteOrigen, $rl->estado);
        self::assertSame(6, $rl->empresaId, 'la empresa NO se cambia automáticamente');
    }

    public function test_baja_en_origen_cierra_la_relacion(): void
    {
        $caso = app(SincronizarRelacionesLaborales::class);
        $caso->ejecutar('05', Periodo::de('2026-07'));
        DB::table('sue001s')->where('id', 15579)->update(['baja' => '2026-08-15']);

        $r = $caso->ejecutar('05', Periodo::de('2026-08'));

        self::assertSame(1, $r->bajas);
        $rl = app(RepositorioRelacionesLaborales::class)->todas(6)[0];
        self::assertSame(EstadoRelacion::Baja, $rl->estado);
        self::assertSame('2026-08-15', $rl->fechaEgreso?->format('Y-m-d'));
    }
}
