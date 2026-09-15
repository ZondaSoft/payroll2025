<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ModeloVacaciones;
use DateTimeInterface;

/** Modelos de vacaciones con sus tramos, asignados por convenio. */
interface RepositorioModelosVacaciones
{
    /** @param list<int> $idsConvenio @return array<int, ModeloVacaciones> */
    public function asignadosAConvenios(array $idsConvenio, DateTimeInterface $fecha): array;

    /** @return list<ModeloVacaciones> */
    public function todos(): array;
}
