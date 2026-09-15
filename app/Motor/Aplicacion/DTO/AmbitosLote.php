<?php

namespace App\Motor\Aplicacion\DTO;

/** Ámbitos de un lote de liquidación, para cargar valores de parámetros y básicos con whereIn (nunca por fila). */
final readonly class AmbitosLote
{
    /**
     * @param  list<int>  $idsConvenio
     * @param  list<int>  $idsCategoria
     * @param  list<int>  $idsEmpresa
     * @param  list<int>  $idsRelacionLaboral
     */
    public function __construct(
        public array $idsConvenio = [],
        public array $idsCategoria = [],
        public array $idsEmpresa = [],
        public array $idsRelacionLaboral = [],
    ) {}
}
