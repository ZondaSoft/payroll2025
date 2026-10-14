<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/**
 * La regla de un perfil sobre un concepto: si aplica, con qué variante y por qué.
 *
 * `aplica = false` es una exclusión explícita y lleva su motivo al recibo: no es
 * lo mismo que la ausencia de regla, que el motor trata como configuración
 * incompleta.
 */
final readonly class PerfilConcepto
{
    public function __construct(
        public ?int $id,
        public string $codigoConcepto,
        public bool $aplica,
        public Vigencia $vigencia,
        public ?string $variante = null,
        public ?string $motivo = null,
        public EstadoValidacion $estadoValidacion = EstadoValidacion::Confirmado,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo_concepto' => $this->codigoConcepto,
            'aplica' => $this->aplica,
            'variante' => $this->variante,
            'motivo' => $this->motivo,
            'estado_validacion' => $this->estadoValidacion->value,
            'vigencia' => $this->vigencia->jsonSerialize(),
        ];
    }
}
