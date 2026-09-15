<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\CasosDeUso\ImportarCatalogosSicoss;
use App\Motor\Aplicacion\CasosDeUso\ImportarIncidenciasDesdeConceptosArca;
use Illuminate\Support\Facades\DB;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

final class ImportarIncidenciasDesdeConceptosArcaTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
    }

    public function test_completa_pendientes_desde_conceptosarcas_sin_pisar_decididas(): void
    {
        DB::table('motor_conceptos')->where('codigo_concepto', 'BASICO')->update(['codigo_afip' => 110000]);
        DB::table('conceptosarcas')->insert(['id_empresa' => 1, 'codigo_afip' => 110000, 'aportes_sipa' => 1, 'contribuciones_sipa' => 1, 'aportes_inssjyp' => 1, 'contribuciones_inssjyp' => 1,
            'aportes_obra_social' => 1, 'contribuciones_obra_social' => 1, 'aportes_fsr' => 1, 'contribuciones_fsr' => 1, 'aportes_renatea' => 0, 'contribuciones_renatea' => 0,
            'contribuciones_aaff' => 1, 'contribuciones_fne' => 1, 'contribuciones_lrt' => 1]);
        $pendientesAntes = DB::table('motor_concepto_incidencia_arca')->where('codigo_concepto', 'BASICO')->where('incidencia', 'PENDIENTE')->count();
        $decididasAntes = DB::table('motor_concepto_incidencia_arca')->where('codigo_concepto', 'BASICO')->whereIn('incidencia', ['S', 'N'])->get()->keyBy('id_subsistema_aplicacion');

        $r = app(ImportarIncidenciasDesdeConceptosArca::class)->ejecutar();

        self::assertSame(1, $r['conceptos_con_codigo_afip']);
        self::assertSame($pendientesAntes, $r['actualizadas']);
        self::assertSame(0, DB::table('motor_concepto_incidencia_arca')->where('codigo_concepto', 'BASICO')->where('incidencia', 'PENDIENTE')->count());
        foreach ($decididasAntes as $id => $fila) {
            self::assertSame($fila->incidencia, DB::table('motor_concepto_incidencia_arca')->where('codigo_concepto', 'BASICO')->where('id_subsistema_aplicacion', $id)->value('incidencia'), 'una S/N decidida no se pisa');
        }
        // RENATEA (0 en legacy) → RENATRE N en el motor
        $renatre = DB::table('motor_concepto_incidencia_arca')->join('motor_subsistema_aplicacion', 'motor_subsistema_aplicacion.id_subsistema_aplicacion', '=', 'motor_concepto_incidencia_arca.id_subsistema_aplicacion')
            ->join('motor_subsistemas', 'motor_subsistemas.id_subsistema', '=', 'motor_subsistema_aplicacion.id_subsistema')
            ->where('codigo_concepto', 'BASICO')->where('motor_subsistemas.codigo', 'RENATRE')->pluck('incidencia')->unique()->all();
        self::assertSame(['N'], $renatre);
    }

    public function test_importa_catalogos_sicoss_sin_pisar(): void
    {
        DB::table('sicoss05s')->insert([['codigo' => 1, 'detalle' => 'OTRO TEXTO PARA 1'], ['codigo' => 9, 'detalle' => 'Nueva condición 9']]);
        DB::table('sicoss_zonas')->insert([['codigo' => 61, 'numero' => '31', 'detalle' => 'Salta'], ['codigo' => 62, 'numero' => '31', 'detalle' => 'Salta bis']]);
        $descripcion1 = DB::table('motor_condiciones_arca')->where('codigo_condicion', '1')->value('descripcion');

        $r = app(ImportarCatalogosSicoss::class)->ejecutar();

        self::assertSame(1, $r['condiciones']);
        self::assertSame(1, $r['localidades'], 'dos zonas con el mismo número ARCA producen una sola localidad');
        self::assertSame($descripcion1, DB::table('motor_condiciones_arca')->where('codigo_condicion', '1')->value('descripcion'), 'la fila del Excel no se pisa');
    }
}
