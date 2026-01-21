<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sicoss08Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $rows = [
            ['codigo' => 1,   'detalle' => 'A tiempo parcial: Indeterminado /permanente'],
            ['codigo' => 2,   'detalle' => 'Becarios - Residencias médicas Ley 22127'],
            ['codigo' => 3,   'detalle' => 'De aprendizaje Ley 25013'],
            ['codigo' => 8,   'detalle' => 'A tiempo completo indeterminado / Trabajo permanente'],
            ['codigo' => 10,  'detalle' => 'Práctica profesionalizante - Dcto. 1374/11 - Pasantías sin obra social'],
            ['codigo' => 11,  'detalle' => 'Trabajo de temporada'],
            ['codigo' => 12,  'detalle' => 'Trabajo eventual'],
            ['codigo' => 14,  'detalle' => 'Nuevo Período de Prueba'],
            ['codigo' => 15,  'detalle' => 'Puesto nuevo varones y mujeres de 25 a 44 años Ley 25250'],
            ['codigo' => 16,  'detalle' => 'Nuevo período de prueba trabajador discapacitado Art. 34 Ley 24147'],
            ['codigo' => 17,  'detalle' => 'Puesto nuevo menor de 25 años, varones y mujeres de 45 o más años y mujer jefe de flia. sin límite de edad Ley 25250'],
            ['codigo' => 18,  'detalle' => 'Trabajador discapacitado Art. 34 Ley 24147'],
            ['codigo' => 19,  'detalle' => 'Puesto nuevo varones y mujeres 25 a 44 años Art. 34 Ley 24147 - Ley 25250'],
            ['codigo' => 20,  'detalle' => 'Puesto nuevo menor de 25 años, varones y mujeres 45 o más y mujer jefe de flia. sin límite de edad Art. 34 Ley 24147 - Ley 25250'],
            ['codigo' => 21,  'detalle' => 'A tiempo parcial determinado (contrato a plazo fijo)'],
            ['codigo' => 22,  'detalle' => 'A tiempo completo determinado (contrato a plazo fijo)'],
            ['codigo' => 23,  'detalle' => 'Personal no permanente Ley 22248'],
            ['codigo' => 24,  'detalle' => 'Personal de la construcción Ley 22250'],
            ['codigo' => 25,  'detalle' => 'Empleo público provincial'],
            ['codigo' => 26,  'detalle' => 'Beneficiario de programa de empleo, capacitación y de recuperación productiva'],
            ['codigo' => 27,  'detalle' => 'Pasantías Ley 26427 - con obra social'],
            ['codigo' => 28,  'detalle' => 'Programas Jefes y Jefas de Hogar'],
            ['codigo' => 29,  'detalle' => 'Decreto Nº 1212/03 - Aportante autónomo'],
            ['codigo' => 30,  'detalle' => 'Nuevo período de prueba trabajador discapacitado Art. 87 Ley 24013'],
            ['codigo' => 31,  'detalle' => 'Trabajador discapacitado Art. 87 Ley 24013'],
            ['codigo' => 44,  'detalle' => 'Changa solidaria - CCT 62/75'],
            ['codigo' => 45,  'detalle' => 'Personal no permanente hoteles CCT 362/03 art. 68 inc. b'],
            ['codigo' => 46,  'detalle' => 'Planta transitoria Adm. Pública Nacional, Provincial y/o Municipal'],
            ['codigo' => 47,  'detalle' => 'Representación gremial'],
            ['codigo' => 48,  'detalle' => 'Art. 4° Ley 24241 - Traslado temporario desde el exterior o convenios bilaterales de Seguridad Social'],
            ['codigo' => 49,  'detalle' => 'Directores - empleado SA con Obra Social y LRT'],
            ['codigo' => 51,  'detalle' => 'Pasantías Ley 26427 - con obra social - beneficiario pensión de discapacidad'],
            ['codigo' => 59,  'detalle' => 'Taller Protegido de Producción Ley 26816 art. 2° punto 2'],
            ['codigo' => 60,  'detalle' => 'Taller Protegido Especial para el Empleo (TPEE) Ley 26816 art. 2° punto 1'],
            ['codigo' => 61,  'detalle' => 'Actor - Intérprete en Empleador Contratante Ley 27.203 - con Obra Social'],
            ['codigo' => 62,  'detalle' => 'Actor - Intérprete en Empleador Contratante Ley 27.203 - sin Obra Social'],
            ['codigo' => 95,  'detalle' => 'Planes Ministerio de Trabajo'],
            ['codigo' => 96,  'detalle' => 'Plan Trabajo por San Luis Ley 5411/03'],
            ['codigo' => 97,  'detalle' => 'Programa Trabajo para jóvenes tucumanos'],
            ['codigo' => 98,  'detalle' => 'BONUS 2da Oportunidad'],
            ['codigo' => 99,  'detalle' => 'LRT (Directores SA, municipios, org., cent. y descent., emp. mixtas, docentes privados o públicos de jurisdicciones incorporadas o no al SIJP)'],
            ['codigo' => 102, 'detalle' => 'Personal permanente discontinuo con ART (para uso de la EU) Decreto Nº 762/14'],
            ['codigo' => 103, 'detalle' => 'Retiro voluntario - Decreto 263/2018 y otros'],
            ['codigo' => 110, 'detalle' => 'Trabajo permanente prestación continua Ley 26727'],
            ['codigo' => 111, 'detalle' => 'Trabajo temporario Ley 26727'],
            ['codigo' => 112, 'detalle' => 'Trabajo permanente discontinuo Ley 26727'],
            ['codigo' => 113, 'detalle' => 'Trabajo por equipo o cuadrilla familiar Ley 26727'],
        ];

        DB::table('sicoss08s')->upsert(
            $rows,
            ['codigo'],
            ['detalle']
        );
    }
}
