<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Aplicacion\DTO\CatalogoSicossFila;

/** Adaptador de sicoss01s/05s/08s/12s/sicoss_zonas. */
interface ProveedorCatalogosSicoss
{
    /** @return list<CatalogoSicossFila> */
    public function actividades(): array;

    /** @return list<CatalogoSicossFila> */
    public function condiciones(): array;

    /** @return list<CatalogoSicossFila> */
    public function modalidades(): array;

    /** @return list<CatalogoSicossFila> */
    public function situaciones(): array;

    /** @return list<CatalogoSicossFila> codigo = numero de 2 caracteres */
    public function localidades(): array;

    /** @return array<int, string> sicoss_zonas.codigo (interno) => numero (2 chars) */
    public function mapaZonaANumero(): array;
}
