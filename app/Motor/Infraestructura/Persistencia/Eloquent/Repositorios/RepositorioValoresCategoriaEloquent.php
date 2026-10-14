<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioValoresCategoria;
use App\Motor\Dominio\Entidades\ValorCategoriaConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorCategoriaConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorCategoriaConceptoVigencia;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

/**
 * VALCAT(): el valor por categoría y su vigencia. La vigencia está en la tabla
 * hija, así que el join es parte de la consulta y no una decisión del dominio.
 */
final class RepositorioValoresCategoriaEloquent implements RepositorioValoresCategoria
{
    public function conceptosDeclarados(): array
    {
        return MotorCategoriaConcepto::query()->where('activo', true)
            ->orderBy('codigo_concepto')->get(['codigo_concepto', 'unidad_valor'])
            ->mapWithKeys(fn ($f) => [$f->codigo_concepto => $f->unidad_valor])->all();
    }

    public function vigentes(DateTimeImmutable $fecha): array
    {
        $q = MotorCategoriaConceptoVigencia::query()
            ->join('motor_categoria_concepto', 'motor_categoria_concepto.id_categoria_concepto', '=',
                'motor_categoria_concepto_vigencia.id_categoria_concepto')
            ->select('motor_categoria_concepto_vigencia.*', 'motor_categoria_concepto.id_categoria',
                'motor_categoria_concepto.codigo_concepto', 'motor_categoria_concepto.tipo_valor',
                'motor_categoria_concepto.unidad_valor', 'motor_categoria_concepto.activo');

        return Mapeos::vigenteA($q, $fecha, 'motor_categoria_concepto_vigencia.')->get()
            ->map(fn ($f) => new ValorCategoriaConcepto(
                (int) $f->id_categoria_concepto_vigencia,
                (int) $f->id_categoria,
                $f->codigo_concepto,
                BigDecimal::of((string) $f->valor),
                Mapeos::vigencia($f),
                $f->tipo_valor,
                $f->unidad_valor,
                Mapeos::estado($f),
                (bool) $f->activo,
            ))->all();
    }
}
