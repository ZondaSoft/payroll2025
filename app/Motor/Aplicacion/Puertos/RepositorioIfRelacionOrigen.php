<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\IfRelacionOrigen;

/** Vínculos identidad legacy ↔ relación laboral. */
interface RepositorioIfRelacionOrigen
{
    /** @param list<string> $claves @return array<string, IfRelacionOrigen> por clave de origen */
    public function porClaves(string $sistema, string $tabla, array $claves): array;

    public function guardar(IfRelacionOrigen $if): IfRelacionOrigen;

    /** @return array<int, int> relacion_laboral_id => id_legajo (clave) */
    public function mapaRelacionPorLegajo(string $sistema, string $tabla): array;
}
