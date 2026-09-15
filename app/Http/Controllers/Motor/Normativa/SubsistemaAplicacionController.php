<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistema;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistemaAplicacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SubsistemaAplicacionController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorSubsistemaAplicacion::class, 'motor.normativa.aplicaciones', 'Aplicación de subsistema', 'Aplicaciones oficiales (subsistema × aporte/contribución)', 'id_subsistema_aplicacion', [
            new CampoCatalogo('codigo_subsistema', 'Subsistema', soloLectura: true, col: 3),
            new CampoCatalogo('tipo_aplicacion', 'Tipo', 'select', obligatorio: true, soloLectura: true, opciones: [['value' => 'APORTE', 'label' => 'Aporte'], ['value' => 'CONTRIBUCION', 'label' => 'Contribución']], col: 3),
            new CampoCatalogo('codigo_base', 'Base REM_n', col: 3, ayuda: 'Vacío si la aplicación no posee base ordinaria propia.'),
            new CampoCatalogo('columna_lsd', 'Columna LSD', 'entero', obligatorio: true, col: 3, ayuda: 'Posición oficial en el archivo de conceptos del LSD.'),
            self::campoActivo(), self::campoEstadoValidacion(),
            new CampoCatalogo('observaciones', 'Observaciones', 'texto_largo', enLista: false, col: 12),
        ], cerrado: true, editablesCerrado: ['activo', 'estado_validacion', 'observaciones'], orden: [['columna_lsd', 'asc']],
            descripcion: 'Combinaciones oficiales válidas. La existencia de la fila expresa habilitación; columna_lsd es la posición en el LSD.');
    }

    protected function alterarConsulta(Builder $q, Request $request): void
    {
        $q->addSelect(['motor_subsistema_aplicacion.*', 'codigo_subsistema' => MotorSubsistema::query()->select('codigo')->whereColumn('motor_subsistemas.id_subsistema', 'motor_subsistema_aplicacion.id_subsistema')]);
    }
}
