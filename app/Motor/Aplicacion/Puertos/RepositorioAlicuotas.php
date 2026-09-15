<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use DateTimeInterface;

/** Alícuotas normativas vigentes a una fecha (tablas chicas: una query cada una). */
interface RepositorioAlicuotas
{
    /** @return list<AlicuotaContribucion> */
    public function contribucionesVigentes(DateTimeInterface $fecha): array;

    /** @return list<AlicuotaAporte> */
    public function aportesVigentes(DateTimeInterface $fecha): array;
}
