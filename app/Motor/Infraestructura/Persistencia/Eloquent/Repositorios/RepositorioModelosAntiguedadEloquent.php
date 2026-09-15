<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioModelosAntiguedad;
use App\Motor\Dominio\Entidades\AsignacionModeloConvenio;
use App\Motor\Dominio\Entidades\ModeloAntiguedad;
use App\Motor\Dominio\Entidades\TramoAntiguedad;
use App\Motor\Dominio\Enums\MetodoAntiguedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAntiguedadTramo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConvenioModeloAntiguedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorModeloAntiguedad;
use Brick\Math\BigDecimal;
use DateTimeInterface;

final class RepositorioModelosAntiguedadEloquent implements RepositorioModelosAntiguedad
{
    public function asignadosAConvenios(array $idsConvenio, DateTimeInterface $fecha): array
    {
        if ($idsConvenio === []) {
            return [];
        }
        $asig = Mapeos::vigenteA(MotorConvenioModeloAntiguedad::query()->whereIn('id_convenio', $idsConvenio), $fecha)->get();
        $modelos = $this->cargar($asig->pluck('id_modelo_antiguedad')->unique()->all());
        $out = [];
        foreach ($asig as $a) {
            if (isset($modelos[(int) $a->id_modelo_antiguedad])) {
                $out[(int) $a->id_convenio] = $modelos[(int) $a->id_modelo_antiguedad];
            }
        }

        return $out;
    }

    public function todos(): array
    {
        return array_values($this->cargar(null));
    }

    public function asignaciones(): array
    {
        return MotorConvenioModeloAntiguedad::query()->orderBy('id_convenio')->get()->map(fn ($a) => new AsignacionModeloConvenio(
            (int) $a->id_asignacion_antiguedad, (int) $a->id_convenio, (int) $a->id_modelo_antiguedad, Mapeos::vigencia($a), Mapeos::estado($a),
        ))->all();
    }

    /** @param list<int>|null $ids @return array<int, ModeloAntiguedad> */
    private function cargar(?array $ids): array
    {
        $q = MotorModeloAntiguedad::query()->orderBy('codigo');
        if ($ids !== null) {
            $q->whereIn('id_modelo_antiguedad', $ids);
        }
        $modelos = $q->get();
        $tramos = MotorAntiguedadTramo::query()->whereIn('id_modelo_antiguedad', $modelos->pluck('id_modelo_antiguedad'))->orderBy('orden')->get()->groupBy('id_modelo_antiguedad');
        $out = [];
        foreach ($modelos as $m) {
            $id = (int) $m->id_modelo_antiguedad;
            $out[$id] = new ModeloAntiguedad($id, $m->codigo, $m->descripcion, MetodoAntiguedad::from($m->metodo_calculo),
                Mapeos::porcentaje($m->porcentaje_por_anio), Mapeos::porcentaje($m->porcentaje_maximo),
                ($tramos[$id] ?? collect())->map(fn ($t) => new TramoAntiguedad(BigDecimal::of((string) $t->anios_desde), Mapeos::decimal($t->anios_hasta), Mapeos::porcentaje($t->porcentaje), (int) $t->orden))->values()->all(),
                Mapeos::estado($m), (bool) $m->activo);
        }

        return $out;
    }
}
