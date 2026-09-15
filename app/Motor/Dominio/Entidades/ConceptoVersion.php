<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Enums\TipoAplicacion;
use App\Motor\Dominio\Enums\TipoCambio;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use DateTimeImmutable;

/**
 * Versión publicada de un concepto: definición congelada (snapshot) + vigencia.
 * El liquidador usa el snapshot, nunca la fila viva (caso 1 del mockup).
 */
final readonly class ConceptoVersion
{
    public const ESQUEMA_ACTUAL = 1;

    /** @param array<string, mixed> $snapshot */
    public function __construct(
        public ?int $id,
        public int $idConcepto,
        public string $codigoConcepto,
        public int $numeroVersion,
        public Vigencia $vigencia,
        public array $snapshot,
        public string $hash,
        public int $esquema = self::ESQUEMA_ACTUAL,
        public ?string $motivo = null,
        public ?TipoCambio $tipoCambio = null,
        public bool $usadaEnLiquidacion = false,
        public ?DateTimeImmutable $publicadaAt = null,
        public ?int $usuarioId = null,
    ) {}

    public function concepto(): Concepto
    {
        return Concepto::desdeDefinicion($this->snapshot['concepto'], $this->idConcepto);
    }

    /** @return list<IncidenciaArca> */
    public function incidenciasArca(): array
    {
        return array_map(fn (array $i) => new IncidenciaArca(
            null, $this->codigoConcepto, (int) ($i['id_subsistema_aplicacion'] ?? 0), $i['codigo_subsistema'],
            TipoAplicacion::from($i['tipo_aplicacion']), Incidencia::from($i['incide']),
            Vigencia::de($i['vigencia']['desde'], $i['vigencia']['hasta'] ?? null), EstadoValidacion::from($i['estado_validacion'] ?? 'SIN_CONFIRMAR'),
        ), $this->snapshot['incidencias_arca'] ?? []);
    }

    /** @return list<IncidenciaBaseZonda> */
    public function incidenciasBaseZonda(): array
    {
        return array_map(fn (array $i) => new IncidenciaBaseZonda(
            null, (int) ($i['id_base_zonda'] ?? 0), $i['codigo_base_zonda'], $this->codigoConcepto, (bool) $i['integra'],
            Vigencia::de($i['vigencia']['desde'], $i['vigencia']['hasta'] ?? null), EstadoValidacion::from($i['estado_validacion'] ?? 'SIN_CONFIRMAR'),
        ), $this->snapshot['bases_zonda'] ?? []);
    }

    /** @return list<ReglaAplicabilidad> */
    public function aplicabilidad(): array
    {
        return array_map(fn (array $r) => new ReglaAplicabilidad(
            null, $this->codigoConcepto, TipoAmbito::from($r['tipo_ambito']), $r['id_convenio'] ?? null, $r['id_categoria'] ?? null,
            $r['id_empresa'] ?? null, $r['relacion_laboral_id'] ?? null, AccionAplicabilidad::from($r['accion']),
            Vigencia::de($r['vigencia']['desde'], $r['vigencia']['hasta'] ?? null), EstadoValidacion::from($r['estado_validacion'] ?? 'SIN_CONFIRMAR'),
        ), $this->snapshot['aplicabilidad'] ?? []);
    }

    public function conVigencia(Vigencia $vigencia): self
    {
        return new self($this->id, $this->idConcepto, $this->codigoConcepto, $this->numeroVersion, $vigencia, $this->snapshot, $this->hash,
            $this->esquema, $this->motivo, $this->tipoCambio, $this->usadaEnLiquidacion, $this->publicadaAt, $this->usuarioId);
    }
}
