<?php

namespace App\Motor\Aplicacion\Puertos;

/** Adaptador de conceptosarcas: marcas por código AFIP (RENATEA → RENATRE). */
interface ProveedorIncidenciasLegacy
{
    /** @return array<int, array<string, bool>> codigo_afip => [ 'SIPA|APORTE' => true, ... ] */
    public function marcasPorCodigoAfip(?int $idEmpresa = null): array;
}
