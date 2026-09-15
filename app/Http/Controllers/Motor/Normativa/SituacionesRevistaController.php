<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSituacionRevistaArca;

class SituacionesRevistaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorSituacionRevistaArca::class, 'motor.normativa.situaciones', 'Situación de revista', 'Situaciones de revista ARCA', 'codigo_situacion', [
            new CampoCatalogo('codigo_situacion', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            self::campoActivo(),
        ], cerrado: false, descripcion: 'Situaciones que ARCA admite informar dentro de un período.');
    }
}
