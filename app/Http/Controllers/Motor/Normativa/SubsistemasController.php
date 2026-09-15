<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistema;

class SubsistemasController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorSubsistema::class, 'motor.normativa.subsistemas', 'Subsistema', 'Subsistemas de la seguridad social', 'codigo', [
            new CampoCatalogo('codigo', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('modulo_responsable', 'Módulo responsable', col: 4),
            new CampoCatalogo('base_aportes', 'Base de aportes', col: 4, ayuda: 'REM_n; vacío si no genera aporte personal.'),
            new CampoCatalogo('base_contribuciones', 'Base de contribuciones', col: 4),
            self::campoActivo(),
        ], cerrado: true, editablesCerrado: ['activo'], orden: [['id_subsistema', 'asc']],
            descripcion: 'Destinos de la recaudación. Ninguna empresa crea, edita ni elimina filas: el F.931 exige la distribución por subsistema.');
    }
}
