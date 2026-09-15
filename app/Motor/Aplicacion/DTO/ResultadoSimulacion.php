<?php

namespace App\Motor\Aplicacion\DTO;

use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;

/** Salida del simulador: la liquidación + datos de cabecera para la UI (no se persiste). */
final readonly class ResultadoSimulacion
{
    /** @param array<string, mixed> $cabecera */
    public function __construct(public Liquidacion $liquidacion, public array $cabecera) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return ['cabecera' => $this->cabecera] + $this->liquidacion->aArray();
    }
}
