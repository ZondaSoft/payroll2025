<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioIfRelacionOrigen;
use App\Motor\Dominio\Entidades\IfRelacionOrigen;

final class IfRelacionOrigenEnMemoria implements RepositorioIfRelacionOrigen
{
    use SecuenciaEnMemoria;

    /** @var list<IfRelacionOrigen> */
    public array $items = [];

    public function porClaves(string $sistema, string $tabla, array $claves): array
    {
        $out = [];
        foreach ($this->items as $i) {
            if ($i->sistemaOrigen === $sistema && $i->tablaOrigen === $tabla && in_array($i->claveOrigen, $claves, true)) {
                $out[$i->claveOrigen] = $i;
            }
        }

        return $out;
    }

    public function guardar(IfRelacionOrigen $if): IfRelacionOrigen
    {
        $n = new IfRelacionOrigen($if->id ?? $this->nuevoId(), $if->relacionLaboralId, $if->sistemaOrigen, $if->tablaOrigen, $if->claveOrigen, $if->codigoLegajoOrigen, $if->hashOrigen, $if->activo);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }

    public function mapaRelacionPorLegajo(string $sistema, string $tabla): array
    {
        $out = [];
        foreach ($this->items as $i) {
            if ($i->sistemaOrigen === $sistema && $i->tablaOrigen === $tabla && $i->activo) {
                $out[$i->relacionLaboralId] = (int) $i->claveOrigen;
            }
        }

        return $out;
    }
}
