<?php

namespace App\Motor\Aplicacion\Puertos;

use DateTimeImmutable;

/**
 * Fuente de tiempo del motor. Siempre en America/Argentina/Buenos_Aires,
 * independiente de config('app.timezone') (que en este sistema es UTC).
 */
interface Reloj
{
    /** Fecha de hoy a las 00:00 (sin hora). */
    public function hoy(): DateTimeImmutable;

    /** Instante actual con hora. */
    public function ahora(): DateTimeImmutable;
}
