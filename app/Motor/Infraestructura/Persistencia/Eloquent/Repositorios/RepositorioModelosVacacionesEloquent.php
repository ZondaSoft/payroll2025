<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioModelosVacaciones;
use App\Motor\Dominio\Entidades\ModeloVacaciones;
use App\Motor\Dominio\Entidades\TramoVacaciones;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConvenioModeloVacaciones;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloVacaciones;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorVacacionesTramo;
use Brick\Math\BigDecimal;
use DateTimeInterface;

final class RepositorioModelosVacacionesEloquent implements RepositorioModelosVacaciones
{
    public function asignadosAConvenios(array $idsConvenio, DateTimeInterface $fecha): array
    {
        if ($idsConvenio === []) {
            return [];
        }
        $asig = Mapeos::vigenteA(MotorConvenioModeloVacaciones::query()->whereIn('id_convenio', $idsConvenio), $fecha)->get();
        $modelos = $this->cargar($asig->pluck('id_modelo_vacaciones')->unique()->all());
        $out = [];
        foreach ($asig as $a) {
            if (isset($modelos[(int) $a->id_modelo_vacaciones])) {
                $out[(int) $a->id_convenio] = $modelos[(int) $a->id_modelo_vacaciones];
            }
        }

        return $out;
    }

    public function todos(): array
    {
        return array_values($this->cargar(null));
    }

    /** @param list<int>|null $ids @return array<int, ModeloVacaciones> */
    private function cargar(?array $ids): array
    {
        $q = MotorModeloVacaciones::query()->orderBy('codigo');
        if ($ids !== null) {
            $q->whereIn('id_modelo_vacaciones', $ids);
        }
        $modelos = $q->get();
        $tramos = MotorVacacionesTramo::query()->whereIn('id_modelo_vacaciones', $modelos->pluck('id_modelo_vacaciones'))->orderBy('orden')->get()->groupBy('id_modelo_vacaciones');
        $out = [];
        foreach ($modelos as $m) {
            $id = (int) $m->id_modelo_vacaciones;
            $out[$id] = new ModeloVacaciones($id, $m->codigo, $m->descripcion,
                ($tramos[$id] ?? collect())->map(fn ($t) => new TramoVacaciones(BigDecimal::of((string) $t->anios_desde), (bool) $t->incluye_desde, Mapeos::decimal($t->anios_hasta), $t->incluye_hasta === null ? null : (bool) $t->incluye_hasta, (int) $t->dias, (int) $t->orden))->values()->all(),
                (bool) $m->activo);
        }

        return $out;
    }
}
