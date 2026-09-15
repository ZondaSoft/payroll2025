<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Dominio\Contratos\CategoriaCanonica;
use Illuminate\Support\Facades\DB;

/** sue006s → CategoriaCanonica. Solo lectura, sin JOIN con tablas del motor. */
final class AdaptadorCategoriasSue006 implements ProveedorCategorias
{
    public function todas(): array
    {
        return DB::table('sue006s')->select('id', 'codigo', 'detalle', 'cod_conve')->orderBy('codigo')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porIds(array $ids): array
    {
        $out = [];
        if ($ids !== []) {
            foreach (DB::table('sue006s')->select('id', 'codigo', 'detalle', 'cod_conve')->whereIn('id', $ids)->get() as $f) {
                $out[(int) $f->id] = $this->mapear($f);
            }
        }

        return $out;
    }

    public function porCodigos(array $codigos): array
    {
        $out = [];
        if ($codigos !== []) {
            foreach (DB::table('sue006s')->select('id', 'codigo', 'detalle', 'cod_conve')->whereIn('codigo', $codigos)->get() as $f) {
                $out[(string) $f->codigo] = $this->mapear($f);
            }
        }

        return $out;
    }

    private function mapear(object $f): CategoriaCanonica
    {
        return new CategoriaCanonica((int) $f->id, (string) $f->codigo, (string) $f->detalle, $f->cod_conve === null || $f->cod_conve === '' ? null : (string) $f->cod_conve);
    }
}
