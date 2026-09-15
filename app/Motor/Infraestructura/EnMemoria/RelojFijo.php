<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Dominio\ValorObjetos\Periodo;
use DateTimeImmutable;
use DateTimeZone;

/** Reloj determinista para tests. */
final class RelojFijo implements Reloj
{
    private DateTimeImmutable $instante;

    public function __construct(string $fechaHora = '2026-07-31 12:00:00')
    {
        $this->instante = new DateTimeImmutable($fechaHora, new DateTimeZone(Periodo::ZONA_HORARIA));
    }

    public function hoy(): DateTimeImmutable
    {
        return $this->instante->setTime(0, 0, 0);
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->instante;
    }

    public function avanzarA(string $fechaHora): void
    {
        $this->instante = new DateTimeImmutable($fechaHora, new DateTimeZone(Periodo::ZONA_HORARIA));
    }
}
