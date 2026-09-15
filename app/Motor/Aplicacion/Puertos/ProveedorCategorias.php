<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Contratos\CategoriaCanonica;

/** Adaptador de sue006s → contrato canónico. */
interface ProveedorCategorias
{
    /** @return list<CategoriaCanonica> */
    public function todas(): array;

    /** @param list<int> $ids @return array<int, CategoriaCanonica> */
    public function porIds(array $ids): array;

    /** @param list<string> $codigos @return array<string, CategoriaCanonica> */
    public function porCodigos(array $codigos): array;
}
