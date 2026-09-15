<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConcepto;

final class RepositorioConceptosEloquent implements RepositorioConceptos
{
    public function todos(bool $soloActivos = false): array
    {
        return MotorConcepto::query()->when($soloActivos, fn ($q) => $q->where('activo', 1))
            ->orderBy('orden_visual_sugerido')->orderBy('codigo_concepto')->get()->map(fn ($f) => self::mapear($f))->all();
    }

    public function porCodigo(CodigoConcepto $codigo): ?Concepto
    {
        $f = MotorConcepto::query()->where('codigo_concepto', (string) $codigo)->first();

        return $f === null ? null : self::mapear($f);
    }

    public function porId(int $id): ?Concepto
    {
        $f = MotorConcepto::query()->find($id);

        return $f === null ? null : self::mapear($f);
    }

    public function guardar(Concepto $c, ?int $usuarioId = null): Concepto
    {
        $datos = self::fila($c);
        if ($c->id === null) {
            $m = MotorConcepto::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorConcepto::query()->findOrFail($c->id);
            unset($datos['codigo_concepto']); // el código natural no se renombra (ON UPDATE RESTRICT)
            $m->update($datos);
        }

        return self::mapear($m->fresh());
    }

    public function bloquearParaPublicar(int $idConcepto): void
    {
        MotorConcepto::query()->whereKey($idConcepto)->lockForUpdate()->first();
    }

    public function cambiarEstadoConfiguracion(int $idConcepto, string $estado): void
    {
        MotorConcepto::query()->whereKey($idConcepto)->update(['estado_configuracion' => $estado]);
    }

    public function darDeBaja(int $idConcepto): void
    {
        MotorConcepto::query()->whereKey($idConcepto)->update(['activo' => 0]);
    }

    public static function mapear(object $f): Concepto
    {
        $d = $f->toArray();
        $d['vigencia'] = ['desde' => (string) $f->vigencia_desde, 'hasta' => $f->vigencia_hasta === null ? null : (string) $f->vigencia_hasta];

        return Concepto::desdeDefinicion($d, (int) $f->id_concepto, (bool) $f->activo,
            EstadoConfiguracion::from($f->estado_configuracion ?? 'BORRADOR'),
            $f->orden_visual_sugerido === null ? null : (int) $f->orden_visual_sugerido, $f->observaciones_funcionales);
    }

    /** @return array<string, mixed> */
    private static function fila(Concepto $c): array
    {
        $d = $c->aDefinicion();
        unset($d['vigencia']);
        $d['id_formula_base'] = null;

        return $d + Mapeos::columnasVigencia($c->vigencia) + [
            'activo' => $c->activo, 'estado_configuracion' => $c->estadoConfiguracion->value,
            'orden_visual_sugerido' => $c->ordenVisual, 'observaciones_funcionales' => $c->observaciones,
        ];
    }
}
