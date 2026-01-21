<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sicoss12Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['codigo' => 1,  'detalle' => 'Activo'],
            ['codigo' => 5,  'detalle' => 'Licencia por maternidad'],
            ['codigo' => 6,  'detalle' => 'Suspensiones por otras causales'],
            ['codigo' => 9,  'detalle' => 'Suspendido. Ley 20744 art. 223 bis'],
            ['codigo' => 10, 'detalle' => 'Licencia por excedencia'],
            ['codigo' => 11, 'detalle' => 'Licencia por maternidad Down'],
            ['codigo' => 12, 'detalle' => 'Licencia por vacaciones'],
            ['codigo' => 13, 'detalle' => 'Licencia sin goce de haberes'],
            ['codigo' => 14, 'detalle' => 'Reserva de puesto'],
            ['codigo' => 15, 'detalle' => 'E.S.E. Cese transitorio de servicios (art. 6 incs. 6 y 7 Dto. 342/92)'],
            ['codigo' => 16, 'detalle' => 'Personal siniestrado de terceros – uso por la ART'],
            ['codigo' => 17, 'detalle' => 'Reingreso por disposición judicial'],
            ['codigo' => 18, 'detalle' => 'ILT – primeros 10 días'],
            ['codigo' => 19, 'detalle' => 'ILT – días 11 y siguientes'],
            ['codigo' => 20, 'detalle' => 'Trabajador siniestrado en nómina de ART'],
            ['codigo' => 21, 'detalle' => 'Trabajador de temporada – reserva de puesto'],
            ['codigo' => 31, 'detalle' => 'Activo – funciones en el exterior'],
            ['codigo' => 32, 'detalle' => 'Licencia por paternidad'],
            ['codigo' => 33, 'detalle' => 'Licencia por fuerza mayor (art. 221 LCT)'],
            ['codigo' => 42, 'detalle' => 'Empleado eventual en EU (uso ESE) – mes completo'],
            ['codigo' => 43, 'detalle' => 'Empleado eventual en EU (uso ESE) – mes incompleto'],
            ['codigo' => 44, 'detalle' => 'Conservación del empleo por accidente o enfermedad inculpable – art. 211 LCT'],
            ['codigo' => 45, 'detalle' => 'Suspensiones por causas disciplinarias'],
        ];

        // agregamos timestamps
        $rows = array_map(fn ($r) => array_merge($r, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $rows);

        DB::table('sicoss12s')->upsert(
            $rows,
            ['codigo'],
            ['detalle', 'updated_at']
        );
    }
}
