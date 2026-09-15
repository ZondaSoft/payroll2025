<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\ValorObjetos\Periodo;

final class NovedadesEnMemoria implements RepositorioNovedades
{
    use SecuenciaEnMemoria;

    /** @var list<Novedad> */
    public array $items = [];

    public function porPeriodoYRelaciones(Periodo $periodo, array $relacionIds): array
    {
        $out = [];
        foreach ($this->items as $n) {
            if ($n->periodo->igualA($periodo) && in_array($n->relacionLaboralId, $relacionIds, true)) {
                $out[$n->relacionLaboralId][] = $n;
            }
        }

        return $out;
    }

    public function guardar(Novedad $n, ?int $usuarioId = null): Novedad
    {
        $x = new Novedad($n->id ?? $this->nuevoId(), $n->periodo, $n->relacionLaboralId, $n->codigoNovedad, $n->cantidad, $n->valor, $n->origen);
        $this->items = $this->reemplazar($this->items, $x);

        return $x;
    }

    public function eliminar(int $id): void
    {
        $this->items = array_values(array_filter($this->items, fn ($n) => $n->id !== $id));
    }
}
