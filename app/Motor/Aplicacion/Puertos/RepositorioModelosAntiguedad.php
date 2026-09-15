<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\AsignacionModeloConvenio;
use App\Motor\Dominio\Entidades\ModeloAntiguedad;
use DateTimeInterface;

/** Modelos de antigüedad con sus tramos, asignados por convenio (legacy id). */
interface RepositorioModelosAntiguedad
{
    /** @param list<int> $idsConvenio @return array<int, ModeloAntiguedad> por id de convenio */
    public function asignadosAConvenios(array $idsConvenio, DateTimeInterface $fecha): array;

    /** @return list<ModeloAntiguedad> */
    public function todos(): array;

    /** @return list<AsignacionModeloConvenio> */
    public function asignaciones(): array;
}
