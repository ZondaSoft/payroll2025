<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorJornadas;
use App\Motor\Dominio\Contratos\JornadaCanonica;
use Illuminate\Support\Facades\DB;

/** sue010s → JornadaCanonica. sue010s no tiene código: se enlaza por id. jornada_id NULL → sinDato(). */
final class AdaptadorJornadasSue010 implements ProveedorJornadas
{
    public function todas(): array
    {
        return DB::table('sue010s')->select('id', 'detalle', 'horas_semana', 'parcial')->orderBy('id')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porId(?int $id): JornadaCanonica
    {
        if ($id === null) {
            return JornadaCanonica::sinDato();
        }
        $f = DB::table('sue010s')->select('id', 'detalle', 'horas_semana', 'parcial')->where('id', $id)->first();

        return $f === null ? JornadaCanonica::sinDato() : $this->mapear($f);
    }

    private function mapear(object $f): JornadaCanonica
    {
        return new JornadaCanonica((int) $f->id, (string) $f->detalle, $f->horas_semana === null ? null : (int) $f->horas_semana, (bool) $f->parcial);
    }
}
