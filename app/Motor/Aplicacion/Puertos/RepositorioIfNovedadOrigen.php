<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\IfNovedadOrigen;
use App\Motor\Dominio\ValorObjetos\Periodo;

/** Procedencia de novedades (incluye las rechazadas). */
interface RepositorioIfNovedadOrigen
{
    /** @param list<string> $claves @return array<string, IfNovedadOrigen> */
    public function porClaves(string $sistema, string $tabla, array $claves): array;

    /** @return list<IfNovedadOrigen> todas las de un período (por prefijo de clave o join) */
    public function delPeriodo(Periodo $periodo, string $sistema, string $tabla): array;

    public function guardar(IfNovedadOrigen $if): IfNovedadOrigen;

    /** @return array<string, int> estado => cantidad */
    public function resumenEstados(): array;
}
