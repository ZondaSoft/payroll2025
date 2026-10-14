<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioPerfiles;
use App\Motor\Dominio\Entidades\Perfil;
use App\Motor\Dominio\Entidades\PerfilConcepto;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorPerfil;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorPerfilConcepto;
use DateTimeImmutable;

final class RepositorioPerfilesEloquent implements RepositorioPerfiles
{
    public function vigentes(DateTimeImmutable $fecha): array
    {
        $perfiles = Mapeos::vigenteA(MotorPerfil::query()->where('activo', true), $fecha)
            ->orderBy('orden_precedencia')->orderBy('codigo_perfil')->get();

        // Una sola consulta para todas las reglas: el resolutor necesita cada perfil
        // con sus conceptos cargados y son pocas filas por período.
        $reglas = Mapeos::vigenteA(MotorPerfilConcepto::query(), $fecha)
            ->whereIn('id_perfil', $perfiles->pluck('id_perfil'))->get()->groupBy('id_perfil');

        return $this->mapear($perfiles, $reglas);
    }

    public function todos(): array
    {
        $perfiles = MotorPerfil::query()->orderBy('orden_precedencia')->orderBy('codigo_perfil')->get();
        $reglas = MotorPerfilConcepto::query()->whereIn('id_perfil', $perfiles->pluck('id_perfil'))
            ->orderBy('codigo_concepto')->get()->groupBy('id_perfil');

        return $this->mapear($perfiles, $reglas);
    }

    public function porId(int $id): ?Perfil
    {
        $p = MotorPerfil::query()->find($id);
        if ($p === null) {
            return null;
        }
        $reglas = MotorPerfilConcepto::query()->where('id_perfil', $id)->orderBy('codigo_concepto')->get();

        return $this->mapear(collect([$p]), collect([$id => $reglas]))[0];
    }

    public function guardar(Perfil $p, ?int $usuarioId = null): Perfil
    {
        $datos = [
            'codigo_perfil' => $p->codigo,
            'nombre' => $p->nombre,
            'nivel' => $p->nivel->value,
            'orden_precedencia' => $p->precedencia(),
            'relacion_laboral_id' => $p->relacionLaboralId,
            'empresa_id' => $p->idEmpresa,
            'id_categoria' => $p->idCategoria,
            'id_convenio' => $p->idConvenio,
            'activo' => $p->activo,
            'observaciones' => $p->observaciones,
        ] + Mapeos::columnasVigencia($p->vigencia);

        if ($p->id === null) {
            $m = MotorPerfil::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorPerfil::query()->findOrFail($p->id);
            $m->update($datos);
        }

        return new Perfil((int) $m->id_perfil, $p->codigo, $p->nombre, $p->nivel, $p->vigencia,
            $p->relacionLaboralId, $p->idEmpresa, $p->idCategoria, $p->idConvenio, $p->activo,
            $p->conceptos, $p->observaciones);
    }

    public function eliminar(int $id): void
    {
        // Baja lógica: un perfil borrado dejaría los conceptos que declaraba sin regla,
        // y el motor pasaría a bloquearlos con PERFIL_SIN_REGLA sin que nadie sepa por qué.
        MotorPerfil::query()->whereKey($id)->update(['activo' => false, 'updated_at' => now()]);
    }

    public function guardarRegla(int $idPerfil, PerfilConcepto $regla): PerfilConcepto
    {
        $datos = [
            'id_perfil' => $idPerfil,
            'codigo_concepto' => $regla->codigoConcepto,
            'aplica' => $regla->aplica,
            'variante' => $regla->variante,
            'motivo' => $regla->motivo,
            'estado_validacion' => $regla->estadoValidacion->value,
        ] + Mapeos::columnasVigencia($regla->vigencia);

        if ($regla->id === null) {
            $m = MotorPerfilConcepto::query()->create($datos);
        } else {
            $m = MotorPerfilConcepto::query()->findOrFail($regla->id);
            $m->update($datos);
        }

        return new PerfilConcepto((int) $m->id_perfil_concepto, $regla->codigoConcepto, $regla->aplica,
            $regla->vigencia, $regla->variante, $regla->motivo, $regla->estadoValidacion);
    }

    public function eliminarRegla(int $idPerfil, int $idRegla): void
    {
        MotorPerfilConcepto::query()->where('id_perfil', $idPerfil)->whereKey($idRegla)->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $perfiles
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $reglas
     * @return list<Perfil>
     */
    private function mapear($perfiles, $reglas): array
    {
        return $perfiles->map(fn ($p) => new Perfil(
            (int) $p->id_perfil,
            $p->codigo_perfil,
            $p->nombre,
            TipoAmbito::from($p->nivel),
            Mapeos::vigencia($p),
            $p->relacion_laboral_id === null ? null : (int) $p->relacion_laboral_id,
            $p->empresa_id === null ? null : (int) $p->empresa_id,
            $p->id_categoria === null ? null : (int) $p->id_categoria,
            $p->id_convenio === null ? null : (int) $p->id_convenio,
            (bool) $p->activo,
            collect($reglas[$p->id_perfil] ?? [])->map(fn ($r) => new PerfilConcepto(
                (int) $r->id_perfil_concepto, $r->codigo_concepto, (bool) $r->aplica,
                Mapeos::vigencia($r), $r->variante, $r->motivo, Mapeos::estado($r),
            ))->values()->all(),
            $p->observaciones,
        ))->values()->all();
    }
}
