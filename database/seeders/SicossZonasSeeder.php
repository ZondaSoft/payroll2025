<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SicossZonas;

class SicossZonasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = [
            ['codigo'=>1, 'numero'=>'02', 'localidad'=>'Buenos Aires - Almte. Brown'],
            ['codigo'=>2, 'numero'=>'02', 'localidad'=>'Buenos Aires - Avellaneda'],
            ['codigo'=>3, 'numero'=>'02', 'localidad'=>'Buenos Aires - Berazategui'],
            ['codigo'=>4, 'numero'=>'03', 'localidad'=>'Buenos Aires - Berisso'],
            ['codigo'=>5, 'numero'=>'03', 'localidad'=>'Buenos Aires - Cañuelas'],
            ['codigo'=>6, 'numero'=>'04', 'localidad'=>'Buenos Aires - Carmen de Patagones'],
            ['codigo'=>7, 'numero'=>'03', 'localidad'=>'Buenos Aires - Ensenada'],
            ['codigo'=>8, 'numero'=>'03', 'localidad'=>'Buenos Aires - Escobar'],
            ['codigo'=>9, 'numero'=>'02', 'localidad'=>'Buenos Aires - Esteban Echeverría'],
            ['codigo'=>10, 'numero'=>'02', 'localidad'=>'Buenos Aires - Florencio Varela'],
            ['codigo'=>11, 'numero'=>'03', 'localidad'=>'Buenos Aires - General Rodríguez'],
            ['codigo'=>12, 'numero'=>'02', 'localidad'=>'Buenos Aires - General San Martín'],
            ['codigo'=>13, 'numero'=>'02', 'localidad'=>'Buenos Aires - General Sarmiento'],
            ['codigo'=>14, 'numero'=>'02', 'localidad'=>'Gran Buenos Aires'],
            ['codigo'=>15, 'numero'=>'02', 'localidad'=>'Buenos Aires - La Matanza'],
            ['codigo'=>16, 'numero'=>'03', 'localidad'=>'Buenos Aires - La Plata'],
            ['codigo'=>17, 'numero'=>'02', 'localidad'=>'Buenos Aires - Lanús'],
            ['codigo'=>18, 'numero'=>'02', 'localidad'=>'Buenos Aires - Lomas de Zamora'],
            ['codigo'=>19, 'numero'=>'03', 'localidad'=>'Buenos Aires - Marcos Paz'],
            ['codigo'=>20, 'numero'=>'02', 'localidad'=>'Buenos Aires - Merlo'],
            ['codigo'=>21, 'numero'=>'02', 'localidad'=>'Buenos Aires - Moreno'],
            ['codigo'=>22, 'numero'=>'02', 'localidad'=>'Buenos Aires - Morón'],
            ['codigo'=>23, 'numero'=>'05', 'localidad'=>'Buenos Aires - Patagones'],
            ['codigo'=>24, 'numero'=>'03', 'localidad'=>'Buenos Aires - Pilar'],
            ['codigo'=>25, 'numero'=>'02', 'localidad'=>'Buenos Aires - Quilmes'],
            ['codigo'=>26, 'numero'=>'07', 'localidad'=>'Buenos Aires - Resto de la Provincia'],
            ['codigo'=>27, 'numero'=>'02', 'localidad'=>'Buenos Aires - San Fernando'],
            ['codigo'=>28, 'numero'=>'02', 'localidad'=>'Buenos Aires - San Isidro'],
            ['codigo'=>29, 'numero'=>'03', 'localidad'=>'Buenos Aires - San Vicente'],
            ['codigo'=>30, 'numero'=>'02', 'localidad'=>'Buenos Aires - Tigre'],
            ['codigo'=>31, 'numero'=>'02', 'localidad'=>'Buenos Aires - Tres de Febrero'],
            ['codigo'=>32, 'numero'=>'02', 'localidad'=>'Buenos Aires - Vicente López'],
            ['codigo'=>33, 'numero'=>'06', 'localidad'=>'Buenos Aires - Villarino'],
            ['codigo'=>34, 'numero'=>'01', 'localidad'=>'Capital Federal'],
            ['codigo'=>35, 'numero'=>'09', 'localidad'=>'Catamarca'],
            ['codigo'=>36, 'numero'=>'D5', 'localidad'=>'Catamarca - Antofagasta de la Sierra (Actividad Minera)'],
            ['codigo'=>37, 'numero'=>'D6', 'localidad'=>'Catamarca - Antofagasta de la Sierra (Resto Actividades)'],
            ['codigo'=>38, 'numero'=>'08', 'localidad'=>'Catamarca - Gran Catamarca'],
            ['codigo'=>39, 'numero'=>'27', 'localidad'=>'Chaco'],
            ['codigo'=>40, 'numero'=>'26', 'localidad'=>'Chaco - Gran Resistencia'],
            ['codigo'=>41, 'numero'=>'29', 'localidad'=>'Chubut'],
            ['codigo'=>42, 'numero'=>'28', 'localidad'=>'Chubut - Rawson'],
            ['codigo'=>43, 'numero'=>'28', 'localidad'=>'Chubut - Trelew'],
            // 👉 (continúa exactamente igual con el resto del listado)
        ];

        foreach ($zonas as $zona) {
            SicossZonas::updateOrCreate(
                ['codigo' => $zona['codigo']],
                [
                    'numero'    => $zona['numero'],
                    'localidad' => $zona['localidad'],
                ]
            );
        }
    }
}
