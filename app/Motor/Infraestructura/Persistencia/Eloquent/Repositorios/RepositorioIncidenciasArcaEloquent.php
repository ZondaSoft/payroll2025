<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\TipoAplicacion;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoIncidenciaArca;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class RepositorioIncidenciasArcaEloquent implements RepositorioIncidenciasArca
{
    public function deConcepto(string $codigoConcepto): array
    {
        return $this->base()->where('motor_concepto_incidencia_arca.codigo_concepto', $codigoConcepto)
            ->orderBy('motor_subsistema_aplicacion.columna_lsd')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function todasVigentes(DateTimeInterface $fecha): array
    {
        $out = [];
        foreach (Mapeos::vigenteA($this->base(), $fecha, 'motor_concepto_incidencia_arca.')->get() as $f) {
            $out[$f->codigo_concepto][] = $this->mapear($f);
        }

        return $out;
    }

    public function guardar(IncidenciaArca $i, ?int $usuarioId = null): IncidenciaArca
    {
        $datos = ['codigo_concepto' => $i->codigoConcepto, 'id_subsistema_aplicacion' => $i->idSubsistemaAplicacion, 'incidencia' => $i->incidencia->value,
            'estado_validacion' => $i->estadoValidacion->value] + Mapeos::columnasVigencia($i->vigencia);
        if ($i->id === null) {
            $m = MotorConceptoIncidenciaArca::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorConceptoIncidenciaArca::query()->findOrFail($i->id);
            $m->update($datos);
        }

        return new IncidenciaArca((int) $m->id_incidencia_arca, $i->codigoConcepto, $i->idSubsistemaAplicacion, $i->codigoSubsistema, $i->tipoAplicacion, $i->incidencia, $i->vigencia, $i->estadoValidacion);
    }

    private function base(): Builder
    {
        return MotorConceptoIncidenciaArca::query()
            ->join('motor_subsistema_aplicacion', 'motor_subsistema_aplicacion.id_subsistema_aplicacion', '=', 'motor_concepto_incidencia_arca.id_subsistema_aplicacion')
            ->join('motor_subsistemas', 'motor_subsistemas.id_subsistema', '=', 'motor_subsistema_aplicacion.id_subsistema')
            ->select('motor_concepto_incidencia_arca.*', 'motor_subsistemas.codigo as codigo_subsistema', 'motor_subsistema_aplicacion.tipo_aplicacion');
    }

    private function mapear(object $f): IncidenciaArca
    {
        return new IncidenciaArca((int) $f->id_incidencia_arca, $f->codigo_concepto, (int) $f->id_subsistema_aplicacion, $f->codigo_subsistema,
            TipoAplicacion::from($f->tipo_aplicacion), Incidencia::from($f->incidencia), Mapeos::vigencia($f), Mapeos::estado($f));
    }
}
