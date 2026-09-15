<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use DateTimeInterface;

/** Encuadramiento patronal A/B por empresa (legacy id) y fecha. */
interface RepositorioEncuadramientos
{
    /** @param list<int> $empresaIds @return array<int, list<EncuadramientoEmpresa>> por empresa */
    public function porEmpresas(array $empresaIds, DateTimeInterface $fecha): array;

    public function guardar(EncuadramientoEmpresa $e, ?int $usuarioId = null): EncuadramientoEmpresa;
}
