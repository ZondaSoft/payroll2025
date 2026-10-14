<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioFormulas;
use App\Motor\Dominio\Formulas\VersionFormula;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoVersion;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class RepositorioFormulasEloquent implements RepositorioFormulas
{
    public function vigentesA(DateTimeImmutable $fecha): array
    {
        $q = MotorConceptoVersion::query()
            ->join('motor_conceptos', 'motor_conceptos.id_concepto', '=', 'motor_concepto_versiones.id_concepto')
            ->select('motor_concepto_versiones.*', 'motor_conceptos.codigo_concepto', 'motor_conceptos.signo')
            ->where('motor_concepto_versiones.estado', 'PUBLICADA')
            // Sólo lo que el DSL puede ejecutar. Los conceptos del bloque contributivo
            // (aportes y contribuciones) están publicados sin expresión porque los
            // calcula el liquidador con las bases ARCA, no una fórmula.
            ->where(fn ($w) => $w->whereRaw("TRIM(COALESCE(motor_concepto_versiones.expr_importe, '')) <> ''")
                ->orWhere(fn ($x) => $x->where('motor_concepto_versiones.metodo', 'MODULO')
                    ->whereNotNull('motor_concepto_versiones.codigo_modulo')));

        $filas = Mapeos::vigenteA($q, $fecha, 'motor_concepto_versiones.')
            ->orderBy('motor_conceptos.codigo_concepto')->orderBy('motor_concepto_versiones.variante')->get();

        $ids = $filas->pluck('id_version');
        $novedades = DB::table('motor_concepto_version_novedad')->whereIn('id_version', $ids)->get()->groupBy('id_version');
        $literales = DB::table('motor_concepto_version_literal')->whereIn('id_version', $ids)->get()->groupBy('id_version');

        return $filas->map(fn ($f) => new VersionFormula(
            codigoConcepto: $f->codigo_concepto,
            variante: $f->variante ?? 'UNICA',
            metodo: $f->metodo ?? VersionFormula::FORMULA,
            exprCondicion: $f->expr_condicion,
            exprCantidad: $f->expr_cantidad,
            exprValorUnitario: $f->expr_valor_unitario,
            exprImporte: $f->expr_importe,
            unidadCantidad: $f->unidad_cantidad,
            codigoModulo: $f->codigo_modulo,
            reglaRedondeo: $f->regla_redondeo_version ?? 'SIN_REDONDEO',
            signo: $f->signo ?? 'CREDITO',
            novedades: ($novedades[$f->id_version] ?? collect())
                ->mapWithKeys(fn ($n) => [$n->codigo_novedad => $n->obligatoriedad])->all(),
            justificaciones: ($literales[$f->id_version] ?? collect())
                ->mapWithKeys(fn ($l) => [$l->literal => $l->justificacion])->all(),
            id: (int) $f->id_version,
        ))->all();
    }
}
