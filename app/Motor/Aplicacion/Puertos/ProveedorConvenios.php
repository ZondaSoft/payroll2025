<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Contratos\ConvenioCanonica;

/** Adaptador de sue007s. */
interface ProveedorConvenios
{
    /** @return list<ConvenioCanonica> */
    public function todos(): array;

    /** @param list<string> $codigos @return array<string, ConvenioCanonica> */
    public function porCodigos(array $codigos): array;

    /** @param list<int> $ids @return array<int, ConvenioCanonica> */
    public function porIds(array $ids): array;
}
