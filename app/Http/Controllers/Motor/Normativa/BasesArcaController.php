<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseArca;

class BasesArcaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorBaseArca::class, 'motor.normativa.bases-arca', 'Base imponible ARCA', 'Bases imponibles ARCA', 'codigo_base', [
            new CampoCatalogo('codigo_base', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('sujeto_obligado', 'Sujeto obligado', 'select', obligatorio: true, opciones: [['value' => 'TRABAJADOR', 'label' => 'Trabajador'], ['value' => 'EMPLEADOR', 'label' => 'Empleador']], col: 3),
            new CampoCatalogo('admite_tope', 'Admite tope', 'booleano', col: 3, ayuda: 'Solo REM_1, REM_4 y REM_5 (bases de aportes) admiten tope.'),
            self::campoActivo(),
        ], cerrado: true, editablesCerrado: ['activo'], orden: [['id_base', 'asc']],
            descripcion: 'Las 10 bases imponibles que exigen el LSD y el F.931. Catálogo cerrado: los códigos son de ARCA y no se renombran.');
    }
}
