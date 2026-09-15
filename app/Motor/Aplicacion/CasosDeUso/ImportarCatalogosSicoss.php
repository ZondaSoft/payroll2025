<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\Puertos\ProveedorCatalogosSicoss;
use App\Motor\Aplicacion\Puertos\RepositorioCatalogosArca;

/**
 * Carga en los catálogos ARCA del motor los catálogos SICOSS que payroll2025 ya
 * tiene validados contra ARCA real. Idempotente: nunca pisa filas existentes.
 */
final class ImportarCatalogosSicoss
{
    public function __construct(
        private readonly ProveedorCatalogosSicoss $sicoss,
        private readonly RepositorioCatalogosArca $catalogos,
    ) {}

    /** @return array<string, int> catálogo => filas insertadas */
    public function ejecutar(): array
    {
        return [
            'actividades' => $this->catalogos->insertarFaltantes('actividades', $this->sicoss->actividades()),
            'condiciones' => $this->catalogos->insertarFaltantes('condiciones', $this->sicoss->condiciones()),
            'modalidades' => $this->catalogos->insertarFaltantes('modalidades', $this->sicoss->modalidades()),
            'situaciones' => $this->catalogos->insertarFaltantes('situaciones', $this->sicoss->situaciones()),
            'localidades' => $this->catalogos->insertarFaltantes('localidades', $this->sicoss->localidades()),
        ];
    }
}
