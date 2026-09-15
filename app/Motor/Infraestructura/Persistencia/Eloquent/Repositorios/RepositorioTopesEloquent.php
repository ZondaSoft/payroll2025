<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioTopes;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorTopeMinimo;

final class RepositorioTopesEloquent implements RepositorioTopes
{
    public function delPeriodo(Periodo $periodo): array
    {
        return MotorTopeMinimo::query()->where('periodo', (string) $periodo)->orderBy('codigo')->get()->map(fn ($f) => new Tope(
            (int) $f->id_tope, $f->codigo, Periodo::de($f->periodo), $f->codigo_base, TipoTope::from($f->tipo_tope),
            ComponenteTope::from($f->componente), Mapeos::importe($f->importe), Mapeos::estado($f),
        ))->all();
    }
}
