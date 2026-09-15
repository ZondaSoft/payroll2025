<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Dominio\Entidades\BaseZonda;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseZonda;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseZondaIncidencia;
use DateTimeInterface;

final class RepositorioBasesZondaEloquent implements RepositorioBasesZonda
{
    public function todas(bool $soloActivas = false): array
    {
        return MotorBaseZonda::query()->when($soloActivas, fn ($q) => $q->where('activo', 1))->orderBy('codigo_base')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porCodigo(string $codigo): ?BaseZonda
    {
        $f = MotorBaseZonda::query()->where('codigo_base', $codigo)->first();

        return $f === null ? null : $this->mapear($f);
    }

    public function guardar(BaseZonda $b, ?int $usuarioId = null): BaseZonda
    {
        $datos = ['codigo_base' => $b->codigo, 'nombre' => $b->nombre, 'descripcion' => $b->descripcion, 'activo' => $b->activo, 'estado_validacion' => $b->estadoValidacion->value];
        if ($b->id === null) {
            $m = MotorBaseZonda::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorBaseZonda::query()->findOrFail($b->id);
            unset($datos['codigo_base']);
            $m->update($datos);
        }

        return $this->mapear($m->fresh());
    }

    public function composicionVigente(DateTimeInterface $fecha): array
    {
        $q = MotorBaseZondaIncidencia::query()
            ->join('motor_bases_zonda', 'motor_bases_zonda.id_base_zonda', '=', 'motor_base_zonda_incidencia.id_base_zonda')
            ->select('motor_base_zonda_incidencia.*', 'motor_bases_zonda.codigo_base as codigo_base_zonda');
        $out = [];
        foreach (Mapeos::vigenteA($q, $fecha, 'motor_base_zonda_incidencia.')->get() as $f) {
            $out[$f->codigo_base_zonda][] = $this->mapearIncidencia($f);
        }

        return $out;
    }

    public function incidenciasDeConcepto(string $codigoConcepto): array
    {
        return MotorBaseZondaIncidencia::query()
            ->join('motor_bases_zonda', 'motor_bases_zonda.id_base_zonda', '=', 'motor_base_zonda_incidencia.id_base_zonda')
            ->select('motor_base_zonda_incidencia.*', 'motor_bases_zonda.codigo_base as codigo_base_zonda')
            ->where('codigo_concepto', $codigoConcepto)->orderBy('codigo_base_zonda')->orderBy('vigencia_desde')
            ->get()->map(fn ($f) => $this->mapearIncidencia($f))->all();
    }

    public function guardarIncidencia(IncidenciaBaseZonda $i, ?int $usuarioId = null): IncidenciaBaseZonda
    {
        $datos = ['id_base_zonda' => $i->idBaseZonda, 'codigo_concepto' => $i->codigoConcepto, 'incidencia' => $i->integra ? 'S' : 'N',
            'estado_validacion' => $i->estadoValidacion->value] + Mapeos::columnasVigencia($i->vigencia);
        if ($i->id === null) {
            $m = MotorBaseZondaIncidencia::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorBaseZondaIncidencia::query()->findOrFail($i->id);
            $m->update($datos);
        }

        return new IncidenciaBaseZonda((int) $m->id_incidencia, $i->idBaseZonda, $i->codigoBaseZonda, $i->codigoConcepto, $i->integra, $i->vigencia, $i->estadoValidacion);
    }

    private function mapear(object $f): BaseZonda
    {
        return new BaseZonda((int) $f->id_base_zonda, $f->codigo_base, $f->nombre, $f->descripcion, (bool) $f->activo, Mapeos::estado($f));
    }

    private function mapearIncidencia(object $f): IncidenciaBaseZonda
    {
        return new IncidenciaBaseZonda((int) $f->id_incidencia, (int) $f->id_base_zonda, $f->codigo_base_zonda, $f->codigo_concepto, strtoupper((string) $f->incidencia) === 'S', Mapeos::vigencia($f), Mapeos::estado($f));
    }
}
