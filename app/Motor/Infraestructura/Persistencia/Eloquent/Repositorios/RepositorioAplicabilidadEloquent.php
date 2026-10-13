<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoAplicabilidad;

/**
 * Las reglas de los cinco ámbitos viven en motor_concepto_aplicabilidad.
 *
 * Hasta el modelo v2, el nivel CATEGORIA se guardaba en motor_categoria_concepto;
 * esa tabla ahora sólo define el valor categórico que lee VALCAT() y perdió sus
 * columnas de acción y vigencia, así que el ámbito CATEGORIA usa id_categoria acá.
 */
final class RepositorioAplicabilidadEloquent implements RepositorioAplicabilidad
{
    public function deConcepto(string $codigoConcepto): array
    {
        return MotorConceptoAplicabilidad::query()
            ->where('codigo_concepto', $codigoConcepto)
            ->orderBy('vigencia_desde')
            ->get()
            ->map(fn ($f) => new ReglaAplicabilidad(
                (int) $f->id_aplicabilidad,
                $f->codigo_concepto,
                TipoAmbito::from($f->tipo_ambito),
                $f->id_convenio === null ? null : (int) $f->id_convenio,
                $f->id_categoria === null ? null : (int) $f->id_categoria,
                $f->id_empresa === null ? null : (int) $f->id_empresa,
                $f->relacion_laboral_id === null ? null : (int) $f->relacion_laboral_id,
                AccionAplicabilidad::from($f->accion),
                Mapeos::vigencia($f),
                Mapeos::estado($f),
                'APLICABILIDAD'
            ))->all();
    }

    public function guardar(ReglaAplicabilidad $r, ?int $usuarioId = null): ReglaAplicabilidad
    {
        $datos = [
            'codigo_concepto' => $r->codigoConcepto,
            'tipo_ambito' => $r->ambito->value,
            'id_convenio' => $r->idConvenio,
            'id_categoria' => $r->idCategoria,
            'id_empresa' => $r->idEmpresa,
            'relacion_laboral_id' => $r->relacionLaboralId,
            'accion' => $r->accion->value,
            'estado_validacion' => $r->estadoValidacion->value,
        ] + Mapeos::columnasVigencia($r->vigencia);

        $m = $r->id === null
            ? MotorConceptoAplicabilidad::query()->create($datos + ['usuario_id' => $usuarioId])
            : tap(MotorConceptoAplicabilidad::query()->findOrFail($r->id))->update($datos);

        return new ReglaAplicabilidad((int) $m->id_aplicabilidad, $r->codigoConcepto, $r->ambito, $r->idConvenio,
            $r->idCategoria, $r->idEmpresa, $r->relacionLaboralId, $r->accion, $r->vigencia, $r->estadoValidacion, 'APLICABILIDAD');
    }

    public function eliminar(int $id, string $origen): void
    {
        MotorConceptoAplicabilidad::query()->whereKey($id)->delete();
    }
}
