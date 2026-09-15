<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use DateTimeInterface;

/** Incidencia de cada concepto en cada aplicación ARCA (fila viva; el liquidador usa el snapshot). */
interface RepositorioIncidenciasArca
{
    /** @return list<IncidenciaArca> */
    public function deConcepto(string $codigoConcepto): array;

    /** @return array<string, list<IncidenciaArca>> por código de concepto */
    public function todasVigentes(DateTimeInterface $fecha): array;

    public function guardar(IncidenciaArca $i, ?int $usuarioId = null): IncidenciaArca;
}
