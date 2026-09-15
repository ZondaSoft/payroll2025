<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioEncuadramientos;
use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use DateTimeInterface;

final class EncuadramientosEnMemoria implements RepositorioEncuadramientos
{
    use SecuenciaEnMemoria;

    /** @var list<EncuadramientoEmpresa> */
    public array $items = [];

    public function porEmpresas(array $empresaIds, DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->items, $fecha) as $e) {
            if (in_array($e->empresaId, $empresaIds, true)) {
                $out[$e->empresaId][] = $e;
            }
        }

        return $out;
    }

    public function guardar(EncuadramientoEmpresa $e, ?int $usuarioId = null): EncuadramientoEmpresa
    {
        $n = new EncuadramientoEmpresa($e->id ?? $this->nuevoId(), $e->empresaId, $e->encuadramiento, $e->vigencia, $e->estadoValidacion, $e->detalle);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }
}
