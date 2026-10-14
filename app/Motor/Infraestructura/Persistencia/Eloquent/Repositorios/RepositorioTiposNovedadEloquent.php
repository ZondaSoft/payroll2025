<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Dominio\Entidades\MapeoNovedad;
use App\Motor\Dominio\Entidades\TipoNovedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorNovedadMapeo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorTipoNovedad;
use Brick\Math\BigDecimal;

final class RepositorioTiposNovedadEloquent implements RepositorioTiposNovedad
{
    public function todos(): array
    {
        return MotorTipoNovedad::query()->orderBy('codigo')->get()->map(fn ($f) => new TipoNovedad((int) $f->id_tipo_novedad, $f->codigo, $f->descripcion, $f->unidad,
            (bool) $f->activo, $f->regla_agregacion, $f->tipo_valor))->all();
    }

    public function mapeos(bool $soloActivos = true): array
    {
        $out = [];
        foreach (MotorNovedadMapeo::query()->when($soloActivos, fn ($q) => $q->where('activo', 1))->orderBy('cod_nov')->get() as $f) {
            $m = new MapeoNovedad((int) $f->id_mapeo, $f->cod_nov, $f->columna_origen, $f->codigo_tipo_novedad, $f->unidad, (bool) $f->es_importe,
                BigDecimal::of((string) $f->factor), $f->accion, (bool) $f->activo);
            $out[$m->clave()] = $m;
        }

        return $out;
    }

    public function guardarMapeo(MapeoNovedad $m, ?int $usuarioId = null): MapeoNovedad
    {
        $datos = ['cod_nov' => $m->codNov, 'columna_origen' => $m->columnaOrigen, 'codigo_tipo_novedad' => $m->codigoTipoNovedad, 'unidad' => $m->unidad,
            'es_importe' => $m->esImporte, 'factor' => (string) $m->factor->toScale(6), 'accion' => $m->accion, 'activo' => $m->activo];
        $f = $m->id === null ? MotorNovedadMapeo::query()->create($datos + ['usuario_id' => $usuarioId]) : tap(MotorNovedadMapeo::query()->findOrFail($m->id))->update($datos);

        return new MapeoNovedad((int) $f->id_mapeo, $m->codNov, $m->columnaOrigen, $m->codigoTipoNovedad, $m->unidad, $m->esImporte, $m->factor, $m->accion, $m->activo);
    }
}
