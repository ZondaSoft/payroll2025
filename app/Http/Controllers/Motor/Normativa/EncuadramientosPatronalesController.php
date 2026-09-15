<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorEncuadramientoPatronal;

class EncuadramientosPatronalesController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorEncuadramientoPatronal::class, 'motor.normativa.encuadramientos-patronales', 'Encuadramiento patronal', 'Encuadramientos patronales (Ley 27.541)', 'encuadramiento', [
            new CampoCatalogo('encuadramiento', 'Código', obligatorio: true, soloLectura: true, col: 2),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 10),
            self::campoFuente(), self::campoActivo(),
        ], cerrado: true, editablesCerrado: ['activo', 'descripcion', 'fuente_normativa'],
            descripcion: 'Dominio cerrado A/B. No guarda porcentajes: viven en las alícuotas de contribuciones con su vigencia.');
    }
}
