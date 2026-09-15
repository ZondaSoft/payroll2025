<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Aplicacion\DTO\NovedadOrigen;
use App\Motor\Dominio\ValorObjetos\Periodo;

/** Adaptador de sue028s: filas crudas del período (cantidad y columnas int_*). */
interface ProveedorNovedadesOrigen
{
    /** @return list<NovedadOrigen> */
    public function delPeriodo(Periodo $periodo): array;

    /** @return array<string, int> cod_nov => cantidad de filas usadas (todo el histórico o un período) */
    public function codigosUsados(?Periodo $periodo = null): array;

    /** @return array<string, string> cod_nov => detalle (sue031s) */
    public function catalogoLegacy(): array;
}
