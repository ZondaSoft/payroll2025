<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\ValorObjetos\Periodo;

/** Novedades normalizadas del período. */
interface RepositorioNovedades
{
    /** @param list<int> $relacionIds @return array<int, list<Novedad>> por relación laboral */
    public function porPeriodoYRelaciones(Periodo $periodo, array $relacionIds): array;

    public function guardar(Novedad $n, ?int $usuarioId = null): Novedad;

    public function eliminar(int $id): void;
}
