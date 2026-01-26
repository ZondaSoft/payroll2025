<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SicossSinie;

class SicossSinieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datos = [
            ['codigo' => 0,  'detalle' => 'No Siniestrado'],
            ['codigo' => 1,  'detalle' => 'ILT Incapacidad Laboral Temporaria'],
            ['codigo' => 2,  'detalle' => 'ILPPP Incapacidad Laboral Permanente Parcial Provisoria'],
            ['codigo' => 3,  'detalle' => 'ILPPD Incapacidad Laboral Permanente Parcial Definitiva'],
            ['codigo' => 4,  'detalle' => 'ILPTP Incapacidad Laboral Permanente Total Provisoria'],
            ['codigo' => 5,  'detalle' => 'Capital de recomposición Art. 15, ap. 3, Ley 24557'],
            ['codigo' => 6,  'detalle' => 'Ajuste Definitivo ILPPD de pago mensual'],
            ['codigo' => 7,  'detalle' => 'RENTA PERIODICA ILPPD Inc Lab Perm Parc Def >50%<66%'],
            ['codigo' => 8,  'detalle' => 'SRT/SSN F. Garantía / F. Reserva ILT Incapacidad Laboral Temporaria'],
            ['codigo' => 9,  'detalle' => 'SRT/SSN F. Garantía / F. Reserva ILPPP Inc Lab Perm Parc Provisoria'],
            ['codigo' => 10, 'detalle' => 'SRT/SSN F. Garantía / F. Reserva ILPTP Inc Lab Perm Total Provisoria'],
            ['codigo' => 11, 'detalle' => 'SRT/SSN F. Garantía / F. Reserva ILPPD Inc Laboral Perm Parc Definitiva'],
            ['codigo' => 12, 'detalle' => 'ILPPD Beneficios devengados art. 11 p. 4'],
            ['codigo' => 13, 'detalle' => 'Informe Incremento salarial de trabajador siniestrado a ART'],
        ];

        foreach ($datos as $item) {
            SicossSinie::updateOrCreate(
                ['codigo' => $item['codigo']],
                ['detalle' => $item['detalle']]
            );
        }
    }
}
