<?php

namespace App\Motor\Infraestructura;

use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Dominio\ValorObjetos\Periodo;
use DateTimeImmutable;
use DateTimeZone;

final class RelojSistema implements Reloj
{
    public function hoy(): DateTimeImmutable
    {
        return $this->ahora()->setTime(0, 0, 0);
    }

    public function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(Periodo::ZONA_HORARIA));
    }
}
