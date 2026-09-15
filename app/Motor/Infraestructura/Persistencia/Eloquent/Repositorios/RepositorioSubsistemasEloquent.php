<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Dominio\Entidades\Subsistema;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Enums\TipoAplicacion;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistema;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistemaAplicacion;

final class RepositorioSubsistemasEloquent implements RepositorioSubsistemas
{
    public function todos(): array
    {
        return MotorSubsistema::query()->orderBy('id_subsistema')->get()->map(fn ($f) => new Subsistema(
            (int) $f->id_subsistema, $f->codigo, $f->descripcion, $f->modulo_responsable, $f->base_aportes, $f->base_contribuciones, (bool) $f->activo,
        ))->all();
    }

    public function aplicaciones(): array
    {
        return MotorSubsistemaAplicacion::query()
            ->join('motor_subsistemas', 'motor_subsistemas.id_subsistema', '=', 'motor_subsistema_aplicacion.id_subsistema')
            ->orderBy('motor_subsistema_aplicacion.columna_lsd')
            ->get(['motor_subsistema_aplicacion.*', 'motor_subsistemas.codigo as codigo_subsistema'])
            ->map(fn ($f) => new SubsistemaAplicacion(
                (int) $f->id_subsistema_aplicacion, (int) $f->id_subsistema, $f->codigo_subsistema, TipoAplicacion::from($f->tipo_aplicacion),
                $f->codigo_base, (int) $f->columna_lsd, (bool) $f->activo, Mapeos::estado($f),
            ))->all();
    }
}
