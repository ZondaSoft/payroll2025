<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Dominio\Entidades\RegimenContributivo;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Dominio\Enums\TipoRegla;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRegimenContributivo;
use DateTimeInterface;

final class RepositorioRegimenesEloquent implements RepositorioRegimenes
{
    public function vigentes(DateTimeInterface $fecha): array
    {
        return Mapeos::vigenteA(MotorRegimenContributivo::query(), $fecha)->orderBy('orden_aplicacion')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function todos(): array
    {
        return MotorRegimenContributivo::query()->orderBy('orden_aplicacion')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porCodigo(string $codigo): ?RegimenContributivo
    {
        $f = MotorRegimenContributivo::query()->where('codigo', $codigo)->first();

        return $f === null ? null : $this->mapear($f);
    }

    private function mapear(object $f): RegimenContributivo
    {
        return new RegimenContributivo(
            (int) $f->id_regimen, $f->codigo, $f->descripcion, TipoRegla::from($f->tipo_regla), SujetoObligado::from($f->sujeto_obligado),
            Mapeos::porcentaje($f->porcentaje), $f->destino, $f->reemplaza_subsistema, $f->base_aplicable, $f->duracion_meses === null ? null : (int) $f->duracion_meses,
            (int) $f->orden_aplicacion, Mapeos::vigencia($f), Mapeos::estado($f),
        );
    }
}
