<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\TipoAplicacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/** Incidencia de un concepto en una aplicación ARCA válida (subsistema × APORTE|CONTRIBUCION). */
final readonly class IncidenciaArca
{
    public function __construct(
        public ?int $id,
        public string $codigoConcepto,
        public int $idSubsistemaAplicacion,
        public string $codigoSubsistema,
        public TipoAplicacion $tipoAplicacion,
        public Incidencia $incidencia,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}

    public function claveAplicacion(): string
    {
        return $this->codigoSubsistema.'|'.$this->tipoAplicacion->value;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo_subsistema' => $this->codigoSubsistema,
            'tipo_aplicacion' => $this->tipoAplicacion->value,
            'incide' => $this->incidencia->value,
            'estado_validacion' => $this->estadoValidacion->value,
            'vigencia' => $this->vigencia->jsonSerialize(),
        ];
    }
}
