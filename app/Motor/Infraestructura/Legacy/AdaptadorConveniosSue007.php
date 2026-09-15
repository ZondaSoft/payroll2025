<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Dominio\Contratos\ConvenioCanonica;
use Illuminate\Support\Facades\DB;

/** sue007s → ConvenioCanonica. */
final class AdaptadorConveniosSue007 implements ProveedorConvenios
{
    public function todos(): array
    {
        return DB::table('sue007s')->select('id', 'codigo', 'detalle', 'hs_normales_semanales')->orderBy('codigo')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porCodigos(array $codigos): array
    {
        $out = [];
        if ($codigos !== []) {
            foreach (DB::table('sue007s')->select('id', 'codigo', 'detalle', 'hs_normales_semanales')->whereIn('codigo', $codigos)->get() as $f) {
                $out[(string) $f->codigo] = $this->mapear($f);
            }
        }

        return $out;
    }

    public function porIds(array $ids): array
    {
        $out = [];
        if ($ids !== []) {
            foreach (DB::table('sue007s')->select('id', 'codigo', 'detalle', 'hs_normales_semanales')->whereIn('id', $ids)->get() as $f) {
                $out[(int) $f->id] = $this->mapear($f);
            }
        }

        return $out;
    }

    private function mapear(object $f): ConvenioCanonica
    {
        return new ConvenioCanonica((int) $f->id, (string) $f->codigo, (string) $f->detalle, $f->hs_normales_semanales === null ? null : (int) $f->hs_normales_semanales);
    }
}
