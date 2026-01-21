<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sicoss01Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datos = [
            ['codigo' => 0, 'detalle' => 'Zona de Desastre. Decreto 1386/01 excepto actividad agropecuaria'],
            ['codigo' => 1, 'detalle' => 'Producción Primaria excepto actividad agropecuaria'],
            ['codigo' => 2, 'detalle' => 'Producción de bienes sin comercialización'],
            ['codigo' => 3, 'detalle' => 'Construcción de inmuebles'],
            ['codigo' => 4, 'detalle' => 'Turismo'],
            ['codigo' => 5, 'detalle' => 'Investigación Científica y Tecnológica'],
            ['codigo' => 6, 'detalle' => 'Administración Pública. CON OBRA SOCIAL 23660'],
            ['codigo' => 7, 'detalle' => 'Enseñanza Privada L.13047 no comprendidos en el D 137/05'],
            ['codigo' => 8, 'detalle' => 'Servicio Doméstico'],
            ['codigo' => 9, 'detalle' => 'Servicios energéticos Res.MTESS 268 y 824/09'],
            ['codigo' => 10, 'detalle' => 'UNIVERSIDADES PRIVADAS. Personal no Docente D.1123/99'],
            ['codigo' => 11, 'detalle' => 'Personal Permanente Discont. Empresas de Servicios Eventuales'],
            ['codigo' => 12, 'detalle' => 'PIT - Programas Intensivos de Trabajo'],
            ['codigo' => 13, 'detalle' => 'Personal embarcado'],
            ['codigo' => 14, 'detalle' => 'Personal embarcado Dec 1255 S/res SSS 18/99'],
            ['codigo' => 15, 'detalle' => 'L.R.T.-Directores SA, municipios, org, cent y descent. Emp mixt provin y otros'],
            ['codigo' => 16, 'detalle' => 'No obligados con el SIJP'],
            ['codigo' => 17, 'detalle' => 'Obligados al SIJP sin Obra Social Nacional'],
            ['codigo' => 18, 'detalle' => 'Provincia incorporada al SIJP sin obra social nacional con ART'],
            ['codigo' => 19, 'detalle' => 'Provincia incorporada al SIJP sin obra social nacional sin ART'],
            ['codigo' => 20, 'detalle' => 'Ley N° 24.331 Zona Franca'],
            ['codigo' => 21, 'detalle' => 'Dec N° 1024/93 Empr del Estado con OS y FNE'],
            ['codigo' => 22, 'detalle' => 'Dec N° 1024/93 Empr del Estado sin OS y con FNE'],
            ['codigo' => 27, 'detalle' => 'Servicio exterior Ley N° 22731'],
            ['codigo' => 29, 'detalle' => 'Personal embarcado Dec 1255 con Obra Social'],

            // ⚠️ código 30 UNA SOLA VEZ
            ['codigo' => 30, 'detalle' => 'AFA Decreto Nº 1212/03. Aportante autónomo'],

            ['codigo' => 31, 'detalle' => 'Trabajador rural de la Armada Argentina Ley 26727'],
            ['codigo' => 32, 'detalle' => 'CSJN Magistrados provinciales con ART'],
            ['codigo' => 33, 'detalle' => 'Representante gremial en uso de licencia'],
            ['codigo' => 34, 'detalle' => 'Docentes estatales nacionales CON OBRA SOCIAL 23660'],
            ['codigo' => 35, 'detalle' => 'Docentes estatales nacionales SIN OBRA SOCIAL 23660'],
            ['codigo' => 36, 'detalle' => 'Investigador/docente universitario estatal CON OBRA SOCIAL'],
            ['codigo' => 37, 'detalle' => 'Investigador/docente universitario estatal SIN OBRA SOCIAL'],
            ['codigo' => 38, 'detalle' => 'Docentes privados Res 71/99 SSS'],
            ['codigo' => 39, 'detalle' => 'No docentes privados Res 71/99 SSS'],
            ['codigo' => 40, 'detalle' => 'Industria del software Ley N° 25922'],
            ['codigo' => 41, 'detalle' => 'Trabajador de la Construcción Ley 25345 art. 36'],
            ['codigo' => 42, 'detalle' => 'Asignaciones Familiares con Obra Social Nacional'],
            ['codigo' => 43, 'detalle' => 'Asignaciones Familiares sin Obra Social Nacional'],
            ['codigo' => 44, 'detalle' => 'Ley N° 24061 Dto.249/92'],
            ['codigo' => 45, 'detalle' => 'Provincia SIJP con obra social nacional con ART'],
            ['codigo' => 46, 'detalle' => 'Provincia SIJP con obra social nacional sin ART'],
            ['codigo' => 47, 'detalle' => 'Ley Nº 15223 con obra social'],
            ['codigo' => 48, 'detalle' => 'Régimen nacional sin obra social nacional'],
            ['codigo' => 49, 'detalle' => 'Actividades no clasificadas'],
            // …
            // podés seguir agregando hasta el 921 sin problemas
        ];

        foreach ($datos as $fila) {
            DB::table('sicoss01s')->updateOrInsert(
                ['codigo' => $fila['codigo']],
                ['detalle' => $fila['detalle']]
            );
        }
    }
}
