<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Dominio\Contratos\EmpresaCanonica;
use Illuminate\Support\Facades\DB;

/** sue086s → EmpresaCanonica. */
final class AdaptadorEmpresasSue086 implements ProveedorEmpresas
{
    public function todas(): array
    {
        return DB::table('sue086s')->select('id', 'codigo', 'detalle', 'cuit')->orderBy('codigo')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porCodigo(string $codigo): ?EmpresaCanonica
    {
        $f = DB::table('sue086s')->select('id', 'codigo', 'detalle', 'cuit')->where('codigo', $codigo)->first();

        return $f === null ? null : $this->mapear($f);
    }

    public function porId(int $id): ?EmpresaCanonica
    {
        $f = DB::table('sue086s')->select('id', 'codigo', 'detalle', 'cuit')->where('id', $id)->first();

        return $f === null ? null : $this->mapear($f);
    }

    private function mapear(object $f): EmpresaCanonica
    {
        return new EmpresaCanonica((int) $f->id, (string) $f->codigo, (string) $f->detalle, $f->cuit === null ? null : (string) $f->cuit);
    }
}
