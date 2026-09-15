<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use DateTimeInterface;

final class RelacionesLaboralesEnMemoria implements RepositorioRelacionesLaborales
{
    use SecuenciaEnMemoria;

    /** @var list<RelacionLaboral> */
    public array $items = [];

    public function porId(int $id): ?RelacionLaboral
    {
        foreach ($this->items as $r) {
            if ($r->id === $id) {
                return $r;
            }
        }

        return null;
    }

    public function porIds(array $ids): array
    {
        return array_values(array_filter($this->items, fn ($r) => in_array($r->id, $ids, true)));
    }

    public function activasPorEmpresa(int $empresaId, DateTimeInterface $fecha): array
    {
        return array_values(array_filter($this->items, fn ($r) => $r->empresaId === $empresaId && $r->estaVigenteEn($fecha)));
    }

    public function todas(?int $empresaId = null): array
    {
        return $empresaId === null ? $this->items : array_values(array_filter($this->items, fn ($r) => $r->empresaId === $empresaId));
    }

    public function guardar(RelacionLaboral $rl, ?int $usuarioId = null): RelacionLaboral
    {
        $n = new RelacionLaboral($rl->id ?? $this->nuevoId(), $rl->idLegajo, $rl->empresaId, $rl->legajo, $rl->fechaIngreso, $rl->fechaEgreso, $rl->estado,
            $rl->cuil, $rl->apellidoNombre, $rl->fechaAntiguedad, $rl->categorias, $rl->encuadreArca, $rl->regimenes, $rl->situaciones);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }
}
