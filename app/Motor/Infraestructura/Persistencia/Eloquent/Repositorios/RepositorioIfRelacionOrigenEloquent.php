<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioIfRelacionOrigen;
use App\Motor\Dominio\Entidades\IfRelacionOrigen;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorIfRelacionLaboralOrigen;

final class RepositorioIfRelacionOrigenEloquent implements RepositorioIfRelacionOrigen
{
    public function porClaves(string $sistema, string $tabla, array $claves): array
    {
        if ($claves === []) {
            return [];
        }
        $out = [];
        foreach (MotorIfRelacionLaboralOrigen::query()->where('sistema_origen', $sistema)->where('tabla_origen', $tabla)->whereIn('clave_origen', $claves)->get() as $f) {
            $out[(string) $f->clave_origen] = $this->mapear($f);
        }

        return $out;
    }

    public function guardar(IfRelacionOrigen $if): IfRelacionOrigen
    {
        $datos = ['relacion_laboral_id' => $if->relacionLaboralId, 'sistema_origen' => $if->sistemaOrigen, 'tabla_origen' => $if->tablaOrigen, 'clave_origen' => $if->claveOrigen,
            'codigo_legajo_origen' => $if->codigoLegajoOrigen, 'hash_origen' => $if->hashOrigen, 'activo' => $if->activo];
        $m = $if->id === null ? MotorIfRelacionLaboralOrigen::query()->create($datos) : tap(MotorIfRelacionLaboralOrigen::query()->findOrFail($if->id))->update($datos);

        return $this->mapear($m->fresh());
    }

    public function mapaRelacionPorLegajo(string $sistema, string $tabla): array
    {
        return MotorIfRelacionLaboralOrigen::query()->where('sistema_origen', $sistema)->where('tabla_origen', $tabla)->where('activo', 1)
            ->pluck('clave_origen', 'relacion_laboral_id')->map(fn ($v) => (int) $v)->all();
    }

    private function mapear(object $f): IfRelacionOrigen
    {
        return new IfRelacionOrigen((int) $f->id_interfaz_relacion, (int) $f->relacion_laboral_id, $f->sistema_origen, $f->tabla_origen, (string) $f->clave_origen, $f->codigo_legajo_origen, $f->hash_origen, (bool) $f->activo);
    }
}
