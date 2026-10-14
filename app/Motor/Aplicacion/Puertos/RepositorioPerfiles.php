<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Perfil;
use DateTimeImmutable;

interface RepositorioPerfiles
{
    /** @return list<Perfil> perfiles activos y vigentes, con sus reglas de concepto cargadas */
    public function vigentes(DateTimeImmutable $fecha): array;
}
