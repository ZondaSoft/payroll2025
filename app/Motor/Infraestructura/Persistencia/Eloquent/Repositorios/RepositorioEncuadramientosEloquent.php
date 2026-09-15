<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioEncuadramientos;
use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorEmpresaEncuadramiento;
use DateTimeInterface;

final class RepositorioEncuadramientosEloquent implements RepositorioEncuadramientos
{
    public function porEmpresas(array $empresaIds, DateTimeInterface $fecha): array
    {
        if ($empresaIds === []) {
            return [];
        }
        $out = [];
        $filas = Mapeos::vigenteA(MotorEmpresaEncuadramiento::query()->whereIn('empresa_id', $empresaIds), $fecha)->orderBy('vigencia_desde')->get();
        foreach ($filas as $f) {
            $out[(int) $f->empresa_id][] = new EncuadramientoEmpresa((int) $f->encuadramiento_id, (int) $f->empresa_id, $f->encuadramiento, Mapeos::vigencia($f), Mapeos::estado($f), $f->detalle);
        }

        return $out;
    }

    public function guardar(EncuadramientoEmpresa $e, ?int $usuarioId = null): EncuadramientoEmpresa
    {
        $datos = ['empresa_id' => $e->empresaId, 'encuadramiento' => $e->encuadramiento, 'detalle' => $e->detalle,
            'estado_validacion' => $e->estadoValidacion->value] + Mapeos::columnasVigencia($e->vigencia);
        if ($e->id === null) {
            $datos['usuario_id'] = $usuarioId;
            $m = MotorEmpresaEncuadramiento::query()->create($datos);
        } else {
            $m = MotorEmpresaEncuadramiento::query()->findOrFail($e->id);
            $m->update($datos);
        }

        return new EncuadramientoEmpresa((int) $m->encuadramiento_id, $e->empresaId, $e->encuadramiento, $e->vigencia, $e->estadoValidacion, $e->detalle);
    }
}
