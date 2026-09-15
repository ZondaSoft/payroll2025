<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\RelacionLaboral;
use DateTimeInterface;

/** Relaciones laborales canónicas con sus hijas (categoría vigente, encuadre, regímenes, situación), cargadas en ≤4 queries. */
interface RepositorioRelacionesLaborales
{
    public function porId(int $id): ?RelacionLaboral;

    /** @param list<int> $ids @return list<RelacionLaboral> */
    public function porIds(array $ids): array;

    /** @return list<RelacionLaboral> */
    public function activasPorEmpresa(int $empresaId, DateTimeInterface $fecha): array;

    /** @return list<RelacionLaboral> */
    public function todas(?int $empresaId = null): array;

    /** Persiste la relación y sus colecciones hijas (upsert por id). Devuelve la relación con ids. */
    public function guardar(RelacionLaboral $rl, ?int $usuarioId = null): RelacionLaboral;
}
