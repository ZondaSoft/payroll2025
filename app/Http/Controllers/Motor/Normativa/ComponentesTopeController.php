<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorComponenteTope;

class ComponentesTopeController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorComponenteTope::class, 'motor.normativa.componentes-tope', 'Componente de tope', 'Componentes de tope', 'codigo_componente', [
            new CampoCatalogo('codigo_componente', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            self::campoActivo(),
        ], cerrado: true, descripcion: 'Catálogo cerrado: MENSUAL, SAC_COMPLETO, SAC_PROPORCIONAL, VACACIONES. Cada uno con cupo propio.');
    }
}
