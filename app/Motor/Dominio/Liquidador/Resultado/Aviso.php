<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

/** Advertencia no bloqueante (p. ej. dato SIN_CONFIRMAR usado en PRUEBA). */
final readonly class Aviso
{
    public function __construct(
        public string $codigo,
        public string $mensaje,
        public ?string $entidad = null,
        public ?string $concepto = null,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return ['codigo' => $this->codigo, 'mensaje' => $this->mensaje, 'entidad' => $this->entidad, 'concepto' => $this->concepto];
    }
}
