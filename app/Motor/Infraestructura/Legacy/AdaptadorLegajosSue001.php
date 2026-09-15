<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Dominio\Contratos\LegajoCanonica;
use App\Motor\Dominio\ValorObjetos\Periodo;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * sue001s → LegajoCanonica. Selecciona solo las columnas que el motor necesita
 * (no las 173). Empresa = grupo_emp (comparación exacta, nunca LIKE). Las
 * fechas '0000-00-00' del legacy se tratan como NULL. sicossLocalidad trae el
 * código INTERNO de zona (sicoss_zonas.codigo); la traducción a `numero` (2
 * chars) la hace el caso de uso con ProveedorCatalogosSicoss::mapaZonaANumero().
 */
final class AdaptadorLegajosSue001 implements ProveedorLegajos
{
    private const COLUMNAS = ['id', 'codigo', 'cuil', 'detalle', 'nombres', 'alta', 'baja', 'fecha_antiguedad_reconocida', 'cod_categ', 'convenio',
        'jornada_id', 'grupo_emp', 'cod_sindic', 'sicoss_activ', 'sicoss_condi', 'sicoss_modal', 'sicoss_situa', 'sicoss_zona', 'bruto'];

    public function activos(?string $codigoEmpresa = null): array
    {
        $q = $this->base()->whereNull('baja')->where('codigo', '>', 0);
        if ($codigoEmpresa !== null) {
            $q->where('grupo_emp', $codigoEmpresa);
        }

        return $q->orderBy('codigo')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porIds(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, 500) as $lote) {
            foreach ($this->base()->whereIn('id', $lote)->get() as $f) {
                $out[(int) $f->id] = $this->mapear($f);
            }
        }

        return $out;
    }

    public function porCodigos(array $codigos): array
    {
        $out = [];
        foreach (array_chunk($codigos, 500) as $lote) {
            foreach ($this->base()->whereIn('codigo', $lote)->get() as $f) {
                $out[(int) $f->codigo] = $this->mapear($f);
            }
        }

        return $out;
    }

    private function base(): Builder
    {
        return DB::table('sue001s')->select(self::COLUMNAS);
    }

    private function mapear(object $f): LegajoCanonica
    {
        $texto = static fn (mixed $v): ?string => ($v === null || trim((string) $v) === '' || (string) $v === '0') ? null : trim((string) $v);

        return new LegajoCanonica(
            id: (int) $f->id,
            codigo: (int) $f->codigo,
            cuil: $texto($f->cuil),
            apellidoNombre: trim(trim((string) $f->detalle).' '.trim((string) $f->nombres)),
            fechaIngreso: self::fecha($f->alta),
            fechaEgreso: self::fecha($f->baja),
            fechaAntiguedad: self::fecha($f->fecha_antiguedad_reconocida),
            codigoCategoria: $texto($f->cod_categ),
            codigoConvenio: $texto($f->convenio),
            jornadaId: $f->jornada_id === null ? null : (int) $f->jornada_id,
            codigoEmpresa: $texto($f->grupo_emp),
            codigoSindicato: $texto($f->cod_sindic),
            sicossCondicion: $texto($f->sicoss_condi),
            sicossActividad: $texto($f->sicoss_activ),
            sicossModalidad: $texto($f->sicoss_modal),
            sicossSituacion: $texto($f->sicoss_situa),
            sicossLocalidad: $texto($f->sicoss_zona),
            bruto: $f->bruto === null ? null : (string) $f->bruto,
        );
    }

    public static function fecha(mixed $v): ?DateTimeImmutable
    {
        if ($v === null) {
            return null;
        }
        $s = substr((string) $v, 0, 10);
        if ($s === '' || str_starts_with($s, '0000')) {
            return null;
        }
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', $s, new DateTimeZone(Periodo::ZONA_HORARIA));

        return $f === false ? null : $f;
    }
}
