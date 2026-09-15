<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioIfNovedadOrigen;
use App\Motor\Dominio\Entidades\IfNovedadOrigen;
use App\Motor\Dominio\Enums\EstadoIfNovedad;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorIfNovedadOrigen;
use DateTimeImmutable;

final class RepositorioIfNovedadOrigenEloquent implements RepositorioIfNovedadOrigen
{
    public function porClaves(string $sistema, string $tabla, array $claves): array
    {
        $out = [];
        foreach (array_chunk($claves, 500) as $lote) {
            foreach (MotorIfNovedadOrigen::query()->where('sistema_origen', $sistema)->where('tabla_origen', $tabla)->whereIn('clave_origen', $lote)->get() as $f) {
                $out[(string) $f->clave_origen] = $this->mapear($f);
            }
        }

        return $out;
    }

    public function delPeriodo(Periodo $periodo, string $sistema, string $tabla): array
    {
        // La clave de origen no lleva período: se resuelve por join con las novedades normalizadas
        // y, para las rechazadas (sin novedad), por el motivo que registra el período.
        return MotorIfNovedadOrigen::query()->where('sistema_origen', $sistema)->where('tabla_origen', $tabla)
            ->where(fn ($w) => $w->whereIn('id_novedad', fn ($q) => $q->select('id_novedad')->from('motor_novedades')->where('periodo', (string) $periodo))
                ->orWhere('motivo', 'like', '%['.(string) $periodo.']%'))
            ->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function guardar(IfNovedadOrigen $if): IfNovedadOrigen
    {
        $datos = ['id_novedad' => $if->idNovedad, 'sistema_origen' => $if->sistemaOrigen, 'tabla_origen' => $if->tablaOrigen, 'clave_origen' => $if->claveOrigen,
            'fecha_importacion' => $if->fechaImportacion->format('Y-m-d H:i:s.u'), 'hash_origen' => $if->hashOrigen, 'estado' => $if->estado->value, 'motivo' => $if->motivo];
        $m = $if->id === null ? MotorIfNovedadOrigen::query()->create($datos) : tap(MotorIfNovedadOrigen::query()->findOrFail($if->id))->update($datos);

        return $this->mapear($m->fresh());
    }

    public function resumenEstados(): array
    {
        return MotorIfNovedadOrigen::query()->selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado')->map(fn ($v) => (int) $v)->all();
    }

    private function mapear(object $f): IfNovedadOrigen
    {
        return new IfNovedadOrigen((int) $f->id_interfaz_novedad, $f->id_novedad === null ? null : (int) $f->id_novedad, $f->sistema_origen, $f->tabla_origen, (string) $f->clave_origen,
            new DateTimeImmutable((string) $f->fecha_importacion), $f->hash_origen, EstadoIfNovedad::from($f->estado), $f->motivo);
    }
}
