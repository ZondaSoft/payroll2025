<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioIfNovedadOrigen;
use App\Motor\Dominio\Entidades\IfNovedadOrigen;
use App\Motor\Dominio\ValorObjetos\Periodo;

final class IfNovedadOrigenEnMemoria implements RepositorioIfNovedadOrigen
{
    use SecuenciaEnMemoria;

    /** @var list<IfNovedadOrigen> */
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

    public function delPeriodo(Periodo $periodo, string $sistema, string $tabla): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->sistemaOrigen === $sistema && $i->tablaOrigen === $tabla && str_contains((string) $i->motivo, '['.$periodo.']')));
    }

    public function guardar(IfNovedadOrigen $if): IfNovedadOrigen
    {
        $n = new IfNovedadOrigen($if->id ?? $this->nuevoId(), $if->idNovedad, $if->sistemaOrigen, $if->tablaOrigen, $if->claveOrigen, $if->fechaImportacion, $if->hashOrigen, $if->estado, $if->motivo);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }

    public function resumenEstados(): array
    {
        $out = [];
        foreach ($this->items as $i) {
            $out[$i->estado->value] = ($out[$i->estado->value] ?? 0) + 1;
        }

        return $out;
    }
}
