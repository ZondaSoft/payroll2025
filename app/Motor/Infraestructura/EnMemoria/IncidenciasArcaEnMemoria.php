<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use DateTimeInterface;

final class IncidenciasArcaEnMemoria implements RepositorioIncidenciasArca
{
    use SecuenciaEnMemoria;

    /** @var list<IncidenciaArca> */
    public array $items = [];

    public function deConcepto(string $codigoConcepto): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->codigoConcepto === $codigoConcepto));
    }

    public function todasVigentes(DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->items, $fecha) as $i) {
            $out[$i->codigoConcepto][] = $i;
        }

        return $out;
    }

    public function guardar(IncidenciaArca $i, ?int $usuarioId = null): IncidenciaArca
    {
        $n = new IncidenciaArca($i->id ?? $this->nuevoId(), $i->codigoConcepto, $i->idSubsistemaAplicacion, $i->codigoSubsistema, $i->tipoAplicacion, $i->incidencia, $i->vigencia, $i->estadoValidacion);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }
}
