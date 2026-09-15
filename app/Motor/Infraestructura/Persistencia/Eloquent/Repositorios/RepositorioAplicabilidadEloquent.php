<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorCategoriaConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoAplicabilidad;

/** Unifica motor_concepto_aplicabilidad (GENERAL/CONVENIO/EMPRESA/TRABAJADOR) y motor_categoria_concepto (CATEGORIA). */
final class RepositorioAplicabilidadEloquent implements RepositorioAplicabilidad
{
    public function deConcepto(string $codigoConcepto): array
    {
        $reglas = MotorConceptoAplicabilidad::query()->where('codigo_concepto', $codigoConcepto)->orderBy('vigencia_desde')->get()
            ->map(fn ($f) => new ReglaAplicabilidad((int) $f->id_aplicabilidad, $f->codigo_concepto, TipoAmbito::from($f->tipo_ambito),
                $f->id_convenio === null ? null : (int) $f->id_convenio, null, $f->id_empresa === null ? null : (int) $f->id_empresa,
                $f->relacion_laboral_id === null ? null : (int) $f->relacion_laboral_id, AccionAplicabilidad::from($f->accion),
                Mapeos::vigencia($f), Mapeos::estado($f), 'APLICABILIDAD'))->all();
        $categorias = MotorCategoriaConcepto::query()->where('codigo_concepto', $codigoConcepto)->orderBy('vigencia_desde')->get()
            ->map(fn ($f) => new ReglaAplicabilidad((int) $f->id_categoria_concepto, $f->codigo_concepto, TipoAmbito::Categoria,
                null, (int) $f->id_categoria, null, null, AccionAplicabilidad::from($f->accion), Mapeos::vigencia($f), Mapeos::estado($f), 'CATEGORIA_CONCEPTO'))->all();

        return array_merge($reglas, $categorias);
    }

    public function guardar(ReglaAplicabilidad $r, ?int $usuarioId = null): ReglaAplicabilidad
    {
        if ($r->ambito === TipoAmbito::Categoria) {
            $datos = ['id_categoria' => $r->idCategoria, 'codigo_concepto' => $r->codigoConcepto, 'accion' => $r->accion->value,
                'estado_validacion' => $r->estadoValidacion->value] + Mapeos::columnasVigencia($r->vigencia);
            $m = $r->id === null ? MotorCategoriaConcepto::query()->create($datos + ['usuario_id' => $usuarioId]) : tap(MotorCategoriaConcepto::query()->findOrFail($r->id))->update($datos);
            $id = (int) $m->id_categoria_concepto;
            $origen = 'CATEGORIA_CONCEPTO';
        } else {
            $datos = ['codigo_concepto' => $r->codigoConcepto, 'tipo_ambito' => $r->ambito->value, 'id_convenio' => $r->idConvenio, 'id_empresa' => $r->idEmpresa,
                'relacion_laboral_id' => $r->relacionLaboralId, 'accion' => $r->accion->value, 'estado_validacion' => $r->estadoValidacion->value] + Mapeos::columnasVigencia($r->vigencia);
            $m = $r->id === null ? MotorConceptoAplicabilidad::query()->create($datos + ['usuario_id' => $usuarioId]) : tap(MotorConceptoAplicabilidad::query()->findOrFail($r->id))->update($datos);
            $id = (int) $m->id_aplicabilidad;
            $origen = 'APLICABILIDAD';
        }

        return new ReglaAplicabilidad($id, $r->codigoConcepto, $r->ambito, $r->idConvenio, $r->idCategoria, $r->idEmpresa, $r->relacionLaboralId, $r->accion, $r->vigencia, $r->estadoValidacion, $origen);
    }

    public function eliminar(int $id, string $origen): void
    {
        if ($origen === 'CATEGORIA_CONCEPTO') {
            MotorCategoriaConcepto::query()->whereKey($id)->delete();
        } else {
            MotorConceptoAplicabilidad::query()->whereKey($id)->delete();
        }
    }
}
