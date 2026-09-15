<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioBasesArca;
use App\Motor\Dominio\Entidades\BaseArca;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseArca;

final class RepositorioBasesArcaEloquent implements RepositorioBasesArca
{
    public function todas(): array
    {
        return MotorBaseArca::query()->orderBy('id_base')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porCodigo(string $codigo): ?BaseArca
    {
        $f = MotorBaseArca::query()->where('codigo_base', $codigo)->first();

        return $f === null ? null : $this->mapear($f);
    }

    private function mapear(object $f): BaseArca
    {
        return new BaseArca($f->codigo_base, $f->descripcion, SujetoObligado::from($f->sujeto_obligado), (bool) $f->admite_tope, (bool) $f->activo);
    }
}
