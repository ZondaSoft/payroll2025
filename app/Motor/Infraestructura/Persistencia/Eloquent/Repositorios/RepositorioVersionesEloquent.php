<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Enums\TipoCambio;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConceptoVersion;
use DateTimeImmutable;
use DateTimeInterface;

final class RepositorioVersionesEloquent implements RepositorioVersiones
{
    public function vigentesA(DateTimeInterface $fecha): array
    {
        $out = [];
        foreach (Mapeos::vigenteA(MotorConceptoVersion::query(), $fecha)->get() as $f) {
            $v = $this->mapear($f);
            $out[$v->codigoConcepto] = $v;
        }

        return $out;
    }

    public function deConcepto(int $idConcepto): array
    {
        return MotorConceptoVersion::query()->where('id_concepto', $idConcepto)->orderBy('vigencia_desde')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function porId(int $idVersion): ?ConceptoVersion
    {
        $f = MotorConceptoVersion::query()->find($idVersion);

        return $f === null ? null : $this->mapear($f);
    }

    public function guardar(ConceptoVersion $v): ConceptoVersion
    {
        $datos = ['id_concepto' => $v->idConcepto, 'numero_version' => $v->numeroVersion, 'esquema_snapshot' => $v->esquema,
            'snapshot' => $v->snapshot, 'hash' => $v->hash, 'motivo' => $v->motivo, 'tipo_cambio' => $v->tipoCambio?->value,
            'publicada_at' => $v->publicadaAt?->format('Y-m-d H:i:s'), 'usada_en_liquidacion' => $v->usadaEnLiquidacion,
            'usuario_id' => $v->usuarioId] + Mapeos::columnasVigencia($v->vigencia);
        if ($v->id === null) {
            $m = MotorConceptoVersion::query()->create($datos);
        } else {
            $m = MotorConceptoVersion::query()->findOrFail($v->id);
            $m->update($datos);
        }

        return $this->mapear($m->fresh());
    }

    public function cerrarVigencia(int $idVersion, DateTimeInterface $hasta): void
    {
        MotorConceptoVersion::query()->whereKey($idVersion)->update(['vigencia_hasta' => $hasta->format('Y-m-d')]);
    }

    public function marcarUsadas(array $idsVersion): void
    {
        if ($idsVersion !== []) {
            MotorConceptoVersion::query()->whereIn('id_version', $idsVersion)->update(['usada_en_liquidacion' => 1]);
        }
    }

    public function conceptosSinVersion(): array
    {
        return MotorConcepto::query()->where('estado_configuracion', 'PUBLICADO')->where('activo', 1)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('motor_concepto_versiones')->whereColumn('motor_concepto_versiones.id_concepto', 'motor_conceptos.id_concepto'))
            ->pluck('id_concepto')->map(fn ($v) => (int) $v)->all();
    }

    private function mapear(object $f): ConceptoVersion
    {
        $snapshot = is_array($f->snapshot) ? $f->snapshot : (array) json_decode((string) $f->snapshot, true);

        return new ConceptoVersion((int) $f->id_version, (int) $f->id_concepto, (string) ($snapshot['concepto']['codigo_concepto'] ?? ''),
            (int) $f->numero_version, Mapeos::vigencia($f), $snapshot, $f->hash, (int) $f->esquema_snapshot, $f->motivo,
            $f->tipo_cambio === null ? null : TipoCambio::from($f->tipo_cambio), (bool) $f->usada_en_liquidacion,
            $f->publicada_at === null ? null : new DateTimeImmutable((string) $f->publicada_at), $f->usuario_id === null ? null : (int) $f->usuario_id);
    }
}
