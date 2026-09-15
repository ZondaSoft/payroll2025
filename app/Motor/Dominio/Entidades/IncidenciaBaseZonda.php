<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/** Decisión explícita: el concepto integra (S) o no (N) una base de Zonda, con vigencia. */
final readonly class IncidenciaBaseZonda
{
    public function __construct(
        public ?int $id,
        public int $idBaseZonda,
        public string $codigoBaseZonda,
        public string $codigoConcepto,
        public bool $integra,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo_base_zonda' => $this->codigoBaseZonda,
            'integra' => $this->integra,
            'estado_validacion' => $this->estadoValidacion->value,
            'vigencia' => $this->vigencia->jsonSerialize(),
        ];
    }
}
