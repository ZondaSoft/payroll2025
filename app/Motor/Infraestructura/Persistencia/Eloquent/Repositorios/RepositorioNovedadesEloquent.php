<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorNovedad;

final class RepositorioNovedadesEloquent implements RepositorioNovedades
{
    public function porPeriodoYRelaciones(Periodo $periodo, array $relacionIds): array
    {
        if ($relacionIds === []) {
            return [];
        }
        $out = [];
        foreach (MotorNovedad::query()->where('periodo', (string) $periodo)->whereIn('relacion_laboral_id', $relacionIds)->orderBy('codigo_novedad')->get() as $f) {
            $out[(int) $f->relacion_laboral_id][] = $this->mapear($f);
        }

        return $out;
    }

    public function guardar(Novedad $n, ?int $usuarioId = null): Novedad
    {
        $datos = ['periodo' => (string) $n->periodo, 'relacion_laboral_id' => $n->relacionLaboralId, 'codigo_novedad' => $n->codigoNovedad,
            'cantidad' => $n->cantidad === null ? null : (string) $n->cantidad->toScale(4), 'valor' => $n->valor === null ? null : (string) $n->valor, 'origen' => $n->origen];
        $m = $n->id === null ? MotorNovedad::query()->create($datos + ['usuario_id' => $usuarioId]) : tap(MotorNovedad::query()->findOrFail($n->id))->update($datos);

        return $this->mapear($m->fresh());
    }

    public function eliminar(int $id): void
    {
        MotorNovedad::query()->whereKey($id)->delete();
    }

    private function mapear(object $f): Novedad
    {
        return new Novedad((int) $f->id_novedad, Periodo::de($f->periodo), (int) $f->relacion_laboral_id, $f->codigo_novedad, Mapeos::decimal($f->cantidad), Mapeos::importe($f->valor), $f->origen);
    }
}
