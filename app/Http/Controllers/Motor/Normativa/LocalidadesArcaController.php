<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorLocalidadArca;

class LocalidadesArcaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorLocalidadArca::class, 'motor.normativa.localidades', 'Localidad ARCA', 'Localidades ARCA', 'codigo_localidad_arca', [
            new CampoCatalogo('codigo_localidad_arca', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('provincia', 'Provincia', col: 4),
            new CampoCatalogo('estado', 'Estado', 'select', opciones: [['value' => 'VIGENTE', 'label' => 'Vigente'], ['value' => 'BAJA', 'label' => 'Baja']], default: 'VIGENTE', col: 3),
            ...self::camposVigencia(false),
        ], cerrado: false, descripcion: 'Códigos de localidad de 2 caracteres (sicoss_zonas.numero).');
    }
}
