<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRegimenContributivo;

class RegimenesController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorRegimenContributivo::class, 'motor.normativa.regimenes', 'Régimen contributivo', 'Regímenes contributivos', 'codigo', [
            new CampoCatalogo('codigo', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('descripcion', 'Descripción', obligatorio: true, col: 9),
            new CampoCatalogo('tipo_regla', 'Tipo de regla', 'select', obligatorio: true, opciones: [['value' => 'BASE', 'label' => 'BASE (punto de partida)'], ['value' => 'SUSTITUTIVO', 'label' => 'SUSTITUTIVO (reemplaza)'], ['value' => 'ADICIONAL', 'label' => 'ADICIONAL (suma)'], ['value' => 'DESTINO', 'label' => 'DESTINO (redirige)']], col: 3),
            new CampoCatalogo('sujeto_obligado', 'Sujeto', 'select', obligatorio: true, opciones: [['value' => 'EMPLEADOR', 'label' => 'Empleador'], ['value' => 'TRABAJADOR', 'label' => 'Trabajador']], col: 3),
            new CampoCatalogo('porcentaje', 'Porcentaje (fracción)', 'porcentaje', col: 3),
            new CampoCatalogo('destino', 'Destino', col: 3, ayuda: 'Subsistemas separados por +: SIPA+INSSJP+AAFF+FNE'),
            new CampoCatalogo('base_aplicable', 'Base aplicable', col: 3),
            new CampoCatalogo('duracion_meses', 'Duración (meses)', 'entero', col: 3, ayuda: 'RIFL: 48 meses desde el ingreso.'),
            new CampoCatalogo('orden_aplicacion', 'Orden', 'entero', obligatorio: true, col: 3, ayuda: 'BASE 10 → SUSTITUTIVO 20 → ADICIONAL 30 → DESTINO 40'),
            ...self::camposVigencia(), self::campoEstadoValidacion(), self::campoFuente(),
        ], cerrado: true, editablesCerrado: ['estado_validacion', 'vigencia_hasta', 'fuente_normativa', 'porcentaje'], orden: [['orden_aplicacion', 'asc'], ['codigo', 'asc']],
            descripcion: 'Desviaciones del régimen general. Ninguna empresa las crea: solo se asignan a trabajadores. "Un chofer con RIFL paga 7 %, no 25 %".');
    }
}
