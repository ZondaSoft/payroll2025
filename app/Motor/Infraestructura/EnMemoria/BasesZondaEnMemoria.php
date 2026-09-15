<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Dominio\Entidades\BaseZonda;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use DateTimeInterface;

final class BasesZondaEnMemoria implements RepositorioBasesZonda
{
    use SecuenciaEnMemoria;

    /** @var list<BaseZonda> */
    public array $bases = [];

    /** @var list<IncidenciaBaseZonda> */
    public array $incidencias = [];

    public function todas(bool $soloActivas = false): array
    {
        return $soloActivas ? array_values(array_filter($this->bases, fn ($b) => $b->activo)) : $this->bases;
    }

    public function porCodigo(string $codigo): ?BaseZonda
    {
        foreach ($this->bases as $b) {
            if ($b->codigo === $codigo) {
                return $b;
            }
        }

        return null;
    }

    public function guardar(BaseZonda $b, ?int $usuarioId = null): BaseZonda
    {
        $n = new BaseZonda($b->id ?? $this->nuevoId(), $b->codigo, $b->nombre, $b->descripcion, $b->activo, $b->estadoValidacion);
        $this->bases = $this->reemplazar($this->bases, $n);

        return $n;
    }

    public function composicionVigente(DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->incidencias, $fecha) as $i) {
            $out[$i->codigoBaseZonda][] = $i;
        }

        return $out;
    }

    public function incidenciasDeConcepto(string $codigoConcepto): array
    {
        return array_values(array_filter($this->incidencias, fn ($i) => $i->codigoConcepto === $codigoConcepto));
    }

    public function guardarIncidencia(IncidenciaBaseZonda $i, ?int $usuarioId = null): IncidenciaBaseZonda
    {
        $n = new IncidenciaBaseZonda($i->id ?? $this->nuevoId(), $i->idBaseZonda, $i->codigoBaseZonda, $i->codigoConcepto, $i->integra, $i->vigencia, $i->estadoValidacion);
        $this->incidencias = $this->reemplazar($this->incidencias, $n);

        return $n;
    }
}
