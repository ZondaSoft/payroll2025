<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorActividadArca;

class ActividadesArcaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorActividadArca::class, 'motor.normativa.actividades', 'Actividad ARCA', 'Actividades ARCA', 'codigo_actividad_arca', [
            new CampoCatalogo('codigo_actividad_arca', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('estado', 'Estado', 'select', opciones: [['value' => 'VIGENTE', 'label' => 'Vigente'], ['value' => 'BAJA', 'label' => 'Baja']], default: 'VIGENTE', col: 3),
            ...self::camposVigencia(false),
        ], cerrado: false, descripcion: 'Códigos de actividad del encuadre SICOSS. Se importan desde sicoss01s.');
    }
}
