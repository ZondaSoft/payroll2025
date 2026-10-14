<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioVariables;
use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Entidades\Variable;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorValorVariable;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorVariable;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

final class RepositorioVariablesEloquent implements RepositorioVariables
{
    public function catalogo(): array
    {
        return MotorVariable::query()->orderBy('codigo')->get()
            ->map(fn ($f) => new Variable($f->codigo, $f->descripcion, $f->unidad, $f->origen, (bool) $f->activo, $f->observaciones))->all();
    }

    public function porCodigo(string $codigo): ?Variable
    {
        $f = MotorVariable::query()->where('codigo', $codigo)->first();

        return $f === null ? null : new Variable($f->codigo, $f->descripcion, $f->unidad, $f->origen, (bool) $f->activo, $f->observaciones);
    }

    public function guardar(Variable $v, ?int $usuarioId = null): Variable
    {
        MotorVariable::query()->updateOrInsert(['codigo' => $v->codigo], [
            'descripcion' => $v->descripcion,
            'unidad' => $v->unidad,
            'origen' => $v->origen,
            'activo' => $v->activo,
            'observaciones' => $v->observaciones,
            'usuario_id' => $usuarioId,
            'updated_at' => now(),
        ]);

        return $v;
    }

    public function valoresDe(string $codigo): array
    {
        return MotorValorVariable::query()->where('codigo', $codigo)
            ->orderBy('alcance')->orderBy('vigencia_desde')->get()->map(fn ($f) => $this->mapearValor($f))->all();
    }

    public function guardarValor(ValorVariable $v, ?int $usuarioId = null): ValorVariable
    {
        $datos = [
            'codigo' => $v->codigo,
            'alcance' => $v->alcance->value,
            'id_convenio' => $v->idConvenio,
            'id_categoria' => $v->idCategoria,
            'relacion_laboral_id' => $v->relacionLaboralId,
            'valor' => (string) $v->valor->toScale(6),
            'estado_validacion' => $v->estadoValidacion->value,
            'fuente' => $v->fuente,
        ] + Mapeos::columnasVigencia($v->vigencia);

        if ($v->id === null) {
            $m = MotorValorVariable::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorValorVariable::query()->findOrFail($v->id);
            $m->update($datos);
        }

        return new ValorVariable((int) $m->id_valor_variable, $v->codigo, $v->alcance, $v->idConvenio, $v->idCategoria,
            $v->relacionLaboralId, $v->valor, $v->vigencia, $v->estadoValidacion, $v->fuente);
    }

    public function eliminarValor(string $codigo, int $idValor): void
    {
        MotorValorVariable::query()->where('codigo', $codigo)->whereKey($idValor)->delete();
    }

    public function valoresVigentes(DateTimeImmutable $fecha): array
    {
        return Mapeos::vigenteA(MotorValorVariable::query(), $fecha)->get()->map(fn ($f) => $this->mapearValor($f))->all();
    }

    private function mapearValor(object $f): ValorVariable
    {
        return new ValorVariable(
            (int) $f->id_valor_variable,
            $f->codigo,
            AlcanceParametro::from($f->alcance),
            $f->id_convenio === null ? null : (int) $f->id_convenio,
            $f->id_categoria === null ? null : (int) $f->id_categoria,
            $f->relacion_laboral_id === null ? null : (int) $f->relacion_laboral_id,
            BigDecimal::of((string) $f->valor),
            Mapeos::vigencia($f),
            Mapeos::estado($f),
            $f->fuente,
        );
    }
}
