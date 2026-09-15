<?php

namespace App\Motor\Dominio\Estrategias;

use RuntimeException;

/** Señal (no bloqueo): el concepto depende de una novedad que no vino este período y no la exige → no se liquida. */
final class SinNovedad extends RuntimeException
{
    public function __construct(public readonly string $codigoNovedad)
    {
        parent::__construct("Sin novedad {$codigoNovedad} en el período");
    }
}
