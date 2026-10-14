<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioVariables;
use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Entidades\Variable;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorValorVariable;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorVariable;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

final class RepositorioVariablesEloquent implements RepositorioVariables
{
    public function catalogo(): array
    {
        return MotorVariable::query()->orderBy('codigo')->get()
            ->map(fn ($f) => new Variable($f->codigo, $f->descripcion, $f->unidad, $f->origen, (bool) $f->activo))->all();
    }

    public function valoresVigentes(DateTimeImmutable $fecha): array
    {
        return Mapeos::vigenteA(MotorValorVariable::query(), $fecha)->get()->map(fn ($f) => new ValorVariable(
            (int) $f->id_valor_variable,
            $f->codigo,
            AlcanceParametro::from($f->alcance),
            $f->id_convenio === null ? null : (int) $f->id_convenio,
            $f->id_categoria === null ? null : (int) $f->id_categoria,
            $f->relacion_laboral_id === null ? null : (int) $f->relacion_laboral_id,
            BigDecimal::of((string) $f->valor),
            Mapeos::vigencia($f),
            Mapeos::estado($f),
            $f->fuente,
        ))->all();
    }
}
