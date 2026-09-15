<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorCondicionArca;

class CondicionesArcaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorCondicionArca::class, 'motor.normativa.condiciones', 'Condición ARCA', 'Condiciones previsionales ARCA', 'codigo_condicion', [
            new CampoCatalogo('codigo_condicion', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('consecuencia_general', 'Consecuencia general', col: 12),
            self::campoActivo(),
        ], cerrado: false, descripcion: 'Selector de reglas de cálculo, no un dato descriptivo. Los códigos son de ARCA y se informan tal cual.');
    }
}
