<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/** Regla INCLUIR/EXCLUIR de un concepto en un ámbito (incluye categoria_concepto como CATEGORIA). */
final readonly class ReglaAplicabilidad
{
    public function __construct(
        public ?int $id,
        public string $codigoConcepto,
        public TipoAmbito $ambito,
        public ?int $idConvenio,
        public ?int $idCategoria,
        public ?int $idEmpresa,
        public ?int $relacionLaboralId,
        public AccionAplicabilidad $accion,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
        public string $origen = 'APLICABILIDAD',
    ) {}

    public function nivel(): int
    {
        return match ($this->ambito) {
            TipoAmbito::Trabajador => 5,
            TipoAmbito::Empresa => 4,
            TipoAmbito::Categoria => 3,
            TipoAmbito::Convenio => 2,
            TipoAmbito::General => 1,
        };
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'tipo_ambito' => $this->ambito->value,
            'id_convenio' => $this->idConvenio,
            'id_categoria' => $this->idCategoria,
            'id_empresa' => $this->idEmpresa,
            'relacion_laboral_id' => $this->relacionLaboralId,
            'accion' => $this->accion->value,
            'estado_validacion' => $this->estadoValidacion->value,
            'vigencia' => $this->vigencia->jsonSerialize(),
        ];
    }
}
