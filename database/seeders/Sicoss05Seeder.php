<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sicoss05Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datos = [
            ['codigo' => 0,  'detalle' => 'Jubilado Decreto N° 894/01 y/o Dec. 2288/02'],
            ['codigo' => 1,  'detalle' => 'Servicios Comunes - Mayor de 18 años'],
            ['codigo' => 2,  'detalle' => 'Jubilado'],
            ['codigo' => 3,  'detalle' => 'Menor'],
            ['codigo' => 5,  'detalle' => 'Servicios Diferenciados - Mayor de 18 años'],
            ['codigo' => 6,  'detalle' => 'Pre-jubilables sin relación de dependencia - Sin servicios reales'],
            ['codigo' => 9,  'detalle' => 'Jubilado Decreto N° 206/00 y/o Decreto N° 894/01'],
            ['codigo' => 10, 'detalle' => 'Pensión (NO SIPA)'],
            ['codigo' => 11, 'detalle' => 'Pensión no contributiva (NO SIPA)'],
            ['codigo' => 12, 'detalle' => 'Art. 8º Ley Nº 27.426'],
            ['codigo' => 13, 'detalle' => 'Servicios diferenciados no alcanzados por el Dto. 633/2018'],
        ];

        foreach ($datos as $fila) {
            DB::table('sicoss05s')->updateOrInsert(
                ['codigo' => $fila['codigo']],
                ['detalle' => $fila['detalle']]
            );
        }
    }
}
