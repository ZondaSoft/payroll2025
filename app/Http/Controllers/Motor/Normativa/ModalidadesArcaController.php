<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModalidadArca;

class ModalidadesArcaController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorModalidadArca::class, 'motor.normativa.modalidades', 'Modalidad ARCA', 'Modalidades de contratación ARCA', 'codigo_modalidad', [
            new CampoCatalogo('codigo_modalidad', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            self::campoActivo(),
        ], cerrado: false, descripcion: 'Nomenclador de modalidades (710 = RIFL). La modalidad no implica por sí sola un régimen contributivo: eso se asigna en la relación laboral.');
    }
}
