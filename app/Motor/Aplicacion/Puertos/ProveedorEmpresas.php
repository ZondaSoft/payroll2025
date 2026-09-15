<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Contratos\EmpresaCanonica;

/** Adaptador de sue086s. */
interface ProveedorEmpresas
{
    /** @return list<EmpresaCanonica> */
    public function todas(): array;

    public function porCodigo(string $codigo): ?EmpresaCanonica;

    public function porId(int $id): ?EmpresaCanonica;
}
