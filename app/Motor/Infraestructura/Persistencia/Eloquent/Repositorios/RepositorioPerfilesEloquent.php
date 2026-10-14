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
            ($reglas[$p->id_perfil] ?? collect())->map(fn ($r) => new PerfilConcepto(
                (int) $r->id_perfil_concepto, $r->codigo_concepto, (bool) $r->aplica,
                Mapeos::vigencia($r), $r->variante, $r->motivo, Mapeos::estado($r),
            ))->values()->all(),
        ))->all();
    }
}
