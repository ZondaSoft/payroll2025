<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Dominio\Entidades\CategoriaVigente;
use App\Motor\Dominio\Entidades\EncuadreArca;
use App\Motor\Dominio\Entidades\RegimenAsignado;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Entidades\SituacionRevista;
use App\Motor\Dominio\Enums\EstadoRelacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboralArca;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboralCategoria;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboralRegimen;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSituacionRevistaPeriodo;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/** Carga por lote: 1 query de relaciones + 4 whereIn para las hijas (nunca por fila). */
final class RepositorioRelacionesLaboralesEloquent implements RepositorioRelacionesLaborales
{
    public function porId(int $id): ?RelacionLaboral
    {
        return $this->porIds([$id])[0] ?? null;
    }

    public function porIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->hidratar(MotorRelacionLaboral::query()->whereIn('relacion_laboral_id', $ids)->orderBy('relacion_laboral_id')->get());
    }

    public function activasPorEmpresa(int $empresaId, DateTimeInterface $fecha): array
    {
        $d = $fecha->format('Y-m-d');
        $q = MotorRelacionLaboral::query()->where('empresa_id', $empresaId)->where('estado', '!=', EstadoRelacion::InconsistenteOrigen->value)
            ->where('fecha_ingreso', '<=', $d)->where(fn ($w) => $w->whereNull('fecha_egreso')->orWhere('fecha_egreso', '>=', $d))->orderBy('legajo');

        return $this->hidratar($q->get());
    }

    public function todas(?int $empresaId = null): array
    {
        return $this->hidratar(MotorRelacionLaboral::query()->when($empresaId !== null, fn ($q) => $q->where('empresa_id', $empresaId))->orderBy('empresa_id')->orderBy('legajo')->get());
    }

    public function guardar(RelacionLaboral $rl, ?int $usuarioId = null): RelacionLaboral
    {
        $datos = ['id_legajo' => $rl->idLegajo, 'empresa_id' => $rl->empresaId, 'legajo' => $rl->legajo, 'cuil' => $rl->cuil, 'apellido_nombre' => $rl->apellidoNombre,
            'fecha_ingreso' => $rl->fechaIngreso->format('Y-m-d'), 'fecha_egreso' => $rl->fechaEgreso?->format('Y-m-d'),
            'fecha_antiguedad' => $rl->fechaAntiguedad?->format('Y-m-d'), 'estado' => $rl->estado->value];
        $m = $rl->id === null ? MotorRelacionLaboral::query()->create($datos + ['usuario_id' => $usuarioId]) : tap(MotorRelacionLaboral::query()->findOrFail($rl->id))->update($datos);
        $id = (int) $m->relacion_laboral_id;

        foreach ($rl->categorias as $c) {
            $d = ['relacion_laboral_id' => $id, 'id_categoria' => $c->idCategoria, 'codigo_categoria' => $c->codigoCategoria, 'estado_validacion' => $c->estadoValidacion->value] + Mapeos::columnasVigencia($c->vigencia);
            $c->id === null ? MotorRelacionLaboralCategoria::query()->create($d + ['usuario_id' => $usuarioId]) : MotorRelacionLaboralCategoria::query()->whereKey($c->id)->update($d);
        }
        if ($rl->encuadreArca !== null) {
            $e = $rl->encuadreArca;
            $d = ['relacion_laboral_id' => $id, 'codigo_condicion' => $e->codigoCondicion, 'actividad_interna' => $e->actividadInterna, 'codigo_actividad_arca' => $e->codigoActividad,
                'codigo_modalidad' => $e->codigoModalidad, 'codigo_localidad_arca' => $e->codigoLocalidad, 'estado_validacion' => $e->estadoValidacion->value] + Mapeos::columnasVigencia($e->vigencia);
            MotorRelacionLaboralArca::query()->updateOrCreate(['relacion_laboral_id' => $id], $d + ($e->id === null ? ['usuario_id' => $usuarioId] : []));
        }
        foreach ($rl->regimenes as $r) {
            $d = ['relacion_laboral_id' => $id, 'id_regimen' => $r->idRegimen, 'estado_validacion' => $r->estadoValidacion->value, 'documento_respaldo' => $r->documentoRespaldo] + Mapeos::columnasVigencia($r->vigencia);
            $r->id === null ? MotorRelacionLaboralRegimen::query()->create($d + ['usuario_id' => $usuarioId]) : MotorRelacionLaboralRegimen::query()->whereKey($r->id)->update($d);
        }
        foreach ($rl->situaciones as $s) {
            $d = ['relacion_laboral_id' => $id, 'periodo' => (string) $s->periodo, 'orden' => $s->orden, 'codigo_situacion' => $s->codigoSituacion, 'dia_desde' => $s->diaDesde, 'dia_hasta' => $s->diaHasta, 'origen_novedad' => $s->origen];
            MotorSituacionRevistaPeriodo::query()->updateOrCreate(['relacion_laboral_id' => $id, 'periodo' => (string) $s->periodo, 'orden' => $s->orden], $d + ($s->id === null ? ['usuario_id' => $usuarioId] : []));
        }

        return $this->porId($id);
    }

    /** @return list<RelacionLaboral> */
    private function hidratar(Collection $filas): array
    {
        if ($filas->isEmpty()) {
            return [];
        }
        $ids = $filas->pluck('relacion_laboral_id')->all();
        $categorias = MotorRelacionLaboralCategoria::query()->whereIn('relacion_laboral_id', $ids)->orderBy('vigencia_desde')->get()->groupBy('relacion_laboral_id');
        $arca = MotorRelacionLaboralArca::query()->whereIn('relacion_laboral_id', $ids)->get()->keyBy('relacion_laboral_id');
        $regimenes = MotorRelacionLaboralRegimen::query()->join('motor_regimenes_contributivos', 'motor_regimenes_contributivos.id_regimen', '=', 'motor_relacion_laboral_regimen.id_regimen')
            ->whereIn('relacion_laboral_id', $ids)->select('motor_relacion_laboral_regimen.*', 'motor_regimenes_contributivos.codigo as codigo_regimen')->orderBy('vigencia_desde')->get()->groupBy('relacion_laboral_id');
        $situaciones = MotorSituacionRevistaPeriodo::query()->whereIn('relacion_laboral_id', $ids)->orderBy('periodo')->orderBy('orden')->get()->groupBy('relacion_laboral_id');

        $out = [];
        foreach ($filas as $f) {
            $id = (int) $f->relacion_laboral_id;
            $a = $arca[$id] ?? null;
            $out[] = new RelacionLaboral(
                id: $id, idLegajo: (int) $f->id_legajo, empresaId: (int) $f->empresa_id, legajo: (string) $f->legajo,
                fechaIngreso: new DateTimeImmutable((string) $f->fecha_ingreso), fechaEgreso: $f->fecha_egreso === null ? null : new DateTimeImmutable((string) $f->fecha_egreso),
                estado: EstadoRelacion::from($f->estado), cuil: $f->cuil, apellidoNombre: $f->apellido_nombre,
                fechaAntiguedad: $f->fecha_antiguedad === null ? null : new DateTimeImmutable((string) $f->fecha_antiguedad),
                categorias: ($categorias[$id] ?? collect())->map(fn ($c) => new CategoriaVigente((int) $c->relacion_categoria_id, $c->id_categoria === null ? null : (int) $c->id_categoria, $c->codigo_categoria, Mapeos::vigencia($c), Mapeos::estado($c)))->values()->all(),
                encuadreArca: $a === null ? null : new EncuadreArca((int) $a->encuadre_sicoss_id, $a->codigo_condicion, $a->actividad_interna, $a->codigo_actividad_arca, $a->codigo_modalidad, $a->codigo_localidad_arca, Mapeos::vigencia($a), Mapeos::estado($a)),
                regimenes: ($regimenes[$id] ?? collect())->map(fn ($r) => new RegimenAsignado((int) $r->relacion_regimen_id, (int) $r->id_regimen, $r->codigo_regimen, Mapeos::vigencia($r), Mapeos::estado($r), $r->documento_respaldo))->values()->all(),
                situaciones: ($situaciones[$id] ?? collect())->map(fn ($s) => new SituacionRevista((int) $s->situacion_periodo_id, Periodo::de($s->periodo), (int) $s->orden, $s->codigo_situacion, (int) $s->dia_desde, (int) $s->dia_hasta, $s->origen_novedad))->values()->all(),
            );
        }

        return $out;
    }
}
