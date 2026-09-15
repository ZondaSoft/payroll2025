<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\DTO\CatalogoSicossFila;
use App\Motor\Aplicacion\Puertos\ProveedorCatalogosSicoss;
use Illuminate\Support\Facades\DB;

/** sicoss01s/05s/08s/12s/sicoss_zonas → filas de catálogo. */
final class AdaptadorCatalogosSicoss implements ProveedorCatalogosSicoss
{
    public function actividades(): array
    {
        return $this->leer('sicoss01s');
    }

    public function condiciones(): array
    {
        return $this->leer('sicoss05s');
    }

    public function modalidades(): array
    {
        return $this->leer('sicoss08s');
    }

    public function situaciones(): array
    {
        return $this->leer('sicoss12s');
    }

    public function localidades(): array
    {
        $out = [];
        foreach (DB::table('sicoss_zonas')->select('numero', 'detalle')->orderBy('numero')->get() as $f) {
            $numero = str_pad(trim((string) $f->numero), 2, '0', STR_PAD_LEFT);
            if ($numero === '00' || isset($out[$numero])) {
                continue; // varias zonas internas comparten el mismo código ARCA de 2 caracteres
            }
            $out[$numero] = new CatalogoSicossFila($numero, (string) $f->detalle);
        }

        return array_values($out);
    }

    public function mapaZonaANumero(): array
    {
        return DB::table('sicoss_zonas')->select('codigo', 'numero')->get()
            ->mapWithKeys(fn ($f) => [(int) $f->codigo => str_pad(trim((string) $f->numero), 2, '0', STR_PAD_LEFT)])->all();
    }

    /** @return list<CatalogoSicossFila> */
    private function leer(string $tabla): array
    {
        return DB::table($tabla)->select('codigo', 'detalle')->orderBy('codigo')->get()
            ->map(fn ($f) => new CatalogoSicossFila((string) $f->codigo, (string) $f->detalle))->all();
    }
}
