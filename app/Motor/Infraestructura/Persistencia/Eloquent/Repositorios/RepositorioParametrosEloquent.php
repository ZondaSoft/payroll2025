<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\DTO\AmbitosLote;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Dominio\Entidades\Parametro;
use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorValorParametro;
use Brick\Math\BigDecimal;
use DateTimeInterface;

final class RepositorioParametrosEloquent implements RepositorioParametros
{
    public function todos(): array
    {
        return MotorParametro::query()->orderBy('codigo')->get()->map(fn ($f) => $this->mapearParametro($f))->all();
    }

    public function porCodigo(string $codigo): ?Parametro
    {
        $f = MotorParametro::query()->where('codigo', $codigo)->first();

        return $f === null ? null : $this->mapearParametro($f);
    }

    public function valoresVigentes(DateTimeInterface $fecha, AmbitosLote $ambitos): array
    {
        $q = MotorValorParametro::query()
            ->join('motor_parametros', 'motor_parametros.id_parametro', '=', 'motor_valores_parametros.id_parametro')
            ->select('motor_valores_parametros.*', 'motor_parametros.codigo as codigo_parametro')
            ->where(function ($w) use ($ambitos) {
                $w->where('alcance', 'GENERAL');
                if ($ambitos->idsConvenio !== []) {
                    $w->orWhere(fn ($x) => $x->where('alcance', 'CONVENIO')->whereIn('id_convenio', $ambitos->idsConvenio));
                }
                if ($ambitos->idsCategoria !== []) {
                    $w->orWhere(fn ($x) => $x->where('alcance', 'CATEGORIA')->whereIn('id_categoria', $ambitos->idsCategoria));
                }
                if ($ambitos->idsEmpresa !== []) {
                    $w->orWhere(fn ($x) => $x->where('alcance', 'EMPRESA')->whereIn('id_empresa', $ambitos->idsEmpresa));
                }
                if ($ambitos->idsRelacionLaboral !== []) {
                    $w->orWhere(fn ($x) => $x->where('alcance', 'TRABAJADOR')->whereIn('relacion_laboral_id', $ambitos->idsRelacionLaboral));
                }
            });

        return Mapeos::vigenteA($q, $fecha, 'motor_valores_parametros.')->get()->map(fn ($f) => $this->mapearValor($f))->all();
    }

    public function valoresDe(int $idParametro): array
    {
        return MotorValorParametro::query()
            ->join('motor_parametros', 'motor_parametros.id_parametro', '=', 'motor_valores_parametros.id_parametro')
            ->select('motor_valores_parametros.*', 'motor_parametros.codigo as codigo_parametro')
            ->where('motor_valores_parametros.id_parametro', $idParametro)->orderBy('alcance')->orderBy('vigencia_desde')
            ->get()->map(fn ($f) => $this->mapearValor($f))->all();
    }

    public function guardarValor(ValorParametro $v, ?int $usuarioId = null): ValorParametro
    {
        $datos = ['id_parametro' => $v->idParametro, 'alcance' => $v->alcance->value, 'id_convenio' => $v->idConvenio, 'id_categoria' => $v->idCategoria,
            'id_empresa' => $v->idEmpresa, 'relacion_laboral_id' => $v->relacionLaboralId, 'valor' => (string) $v->valor->toScale(6),
            'estado_validacion' => $v->estadoValidacion->value] + Mapeos::columnasVigencia($v->vigencia);
        if ($v->id === null) {
            $m = MotorValorParametro::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorValorParametro::query()->findOrFail($v->id);
            $m->update($datos);
        }

        return new ValorParametro((int) $m->id_valor_parametro, $v->idParametro, $v->codigoParametro, $v->alcance, $v->idConvenio, $v->idCategoria,
            $v->idEmpresa, $v->relacionLaboralId, $v->valor, $v->vigencia, $v->estadoValidacion);
    }

    private function mapearParametro(object $f): Parametro
    {
        return new Parametro((int) $f->id_parametro, $f->codigo, $f->descripcion, $f->tipo_parametro, $f->unidad, (bool) $f->es_tecnico, (bool) $f->activo);
    }

    private function mapearValor(object $f): ValorParametro
    {
        return new ValorParametro((int) $f->id_valor_parametro, (int) $f->id_parametro, $f->codigo_parametro, AlcanceParametro::from($f->alcance),
            $f->id_convenio === null ? null : (int) $f->id_convenio, $f->id_categoria === null ? null : (int) $f->id_categoria,
            $f->id_empresa === null ? null : (int) $f->id_empresa, $f->relacion_laboral_id === null ? null : (int) $f->relacion_laboral_id,
            BigDecimal::of((string) $f->valor), Mapeos::vigencia($f), Mapeos::estado($f));
    }
}
