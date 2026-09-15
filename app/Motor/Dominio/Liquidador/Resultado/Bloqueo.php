<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;

/** Bloqueo registrado en el resultado (fail-closed): qué, por qué, dónde y en qué etapa. */
final readonly class Bloqueo
{
    public function __construct(
        public CodigoBloqueo $codigo,
        public string $motivo,
        public string $etapa,
        public ?string $entidad = null,
        public ?string $campo = null,
        public bool $fatal = false,
        public ?string $concepto = null,
    ) {}

    public static function desde(MotorBloqueado $e, string $etapa, ?string $concepto = null): self
    {
        return new self($e->codigo, $e->getMessage(), $etapa, $e->entidad, $e->campo, $e->fatal, $concepto);
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo' => $this->codigo->value, 'titulo' => $this->codigo->etiqueta(), 'motivo' => $this->motivo, 'etapa' => $this->etapa,
            'entidad' => $this->entidad, 'campo' => $this->campo, 'fatal' => $this->fatal, 'concepto' => $this->concepto,
        ];
    }
}
