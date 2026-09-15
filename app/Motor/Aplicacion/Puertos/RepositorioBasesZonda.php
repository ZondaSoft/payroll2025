<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\BaseZonda;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use DateTimeInterface;

/** Bases de Zonda y su composición vigente (para la fila viva y la UI; el liquidador usa el snapshot). */
interface RepositorioBasesZonda
{
    /** @return list<BaseZonda> */
    public function todas(bool $soloActivas = false): array;

    public function porCodigo(string $codigo): ?BaseZonda;

    public function guardar(BaseZonda $b, ?int $usuarioId = null): BaseZonda;

    /** @return array<string, list<IncidenciaBaseZonda>> por código de base, vigentes a la fecha */
    public function composicionVigente(DateTimeInterface $fecha): array;

    /** @return list<IncidenciaBaseZonda> */
    public function incidenciasDeConcepto(string $codigoConcepto): array;

    public function guardarIncidencia(IncidenciaBaseZonda $i, ?int $usuarioId = null): IncidenciaBaseZonda;
}
