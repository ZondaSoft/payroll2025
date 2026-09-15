<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\DTO\NovedadOrigen;
use App\Motor\Aplicacion\Puertos\ProveedorNovedadesOrigen;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Illuminate\Support\Facades\DB;

/**
 * sue028s → filas crudas. La polimorfía del legacy (cantidad = días, horas, km o
 * pesos según cod_nov; además columnas int_*) se resuelve después con
 * motor_novedad_mapeo. Acá solo se exponen las columnas con valor.
 */
final class AdaptadorNovedadesSue028 implements ProveedorNovedadesOrigen
{
    private const COLUMNAS_EXTRA = ['int_vac', 'int_enf', 'int_acc', 'int_susp', 'int_aperc', 'int_licencias'];

    public function delPeriodo(Periodo $periodo): array
    {
        $out = [];
        $filas = DB::table('sue028s')->select(array_merge(['id', 'periodo', 'legajo', 'cod_nov', 'cantidad'], self::COLUMNAS_EXTRA))
            ->where('periodo', $periodo->yyyymm())->whereNotNull('legajo')->whereNotNull('cod_nov')->orderBy('id')->cursor();
        foreach ($filas as $f) {
            $out[] = new NovedadOrigen((int) $f->id, (string) $f->periodo, (int) $f->legajo, (string) $f->cod_nov, 'cantidad', (string) $f->cantidad);
            foreach (self::COLUMNAS_EXTRA as $col) {
                if ($f->{$col} !== null && (int) $f->{$col} > 0) {
                    $out[] = new NovedadOrigen((int) $f->id, (string) $f->periodo, (int) $f->legajo, (string) $f->cod_nov, $col, (string) $f->{$col});
                }
            }
        }

        return $out;
    }

    public function codigosUsados(?Periodo $periodo = null): array
    {
        $q = DB::table('sue028s')->selectRaw('cod_nov, count(*) as n')->whereNotNull('cod_nov')->groupBy('cod_nov')->orderByDesc('n');
        if ($periodo !== null) {
            $q->where('periodo', $periodo->yyyymm());
        }

        return $q->pluck('n', 'cod_nov')->map(fn ($v) => (int) $v)->all();
    }

    public function catalogoLegacy(): array
    {
        return DB::table('sue031s')->select('codigo', 'detalle')->orderBy('codigo')->pluck('detalle', 'codigo')->map(fn ($v) => (string) $v)->all();
    }
}
