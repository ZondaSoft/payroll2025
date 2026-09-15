<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Contratos\LegajoCanonica;

/** Adaptador de sue001s: solo las columnas necesarias, por lote. */
interface ProveedorLegajos
{
    /** @return list<LegajoCanonica> activos (baja IS NULL) de la empresa (sue086s.codigo), o de todas si null */
    public function activos(?string $codigoEmpresa = null): array;

    /** @param list<int> $ids @return array<int, LegajoCanonica> por sue001s.id (activos o no) */
    public function porIds(array $ids): array;

    /** @param list<int> $codigos @return array<int, LegajoCanonica> por número de legajo */
    public function porCodigos(array $codigos): array;
}
