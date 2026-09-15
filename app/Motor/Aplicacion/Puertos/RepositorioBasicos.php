<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Basico;
use DateTimeInterface;

/** Sueldos básicos por categoría (legacy id) y vigencia. */
interface RepositorioBasicos
{
    /** @param list<int> $idsCategoria @return array<int, list<Basico>> vigentes a la fecha (más de uno = ambiguo) */
    public function porCategorias(array $idsCategoria, DateTimeInterface $fecha): array;

    /** @return list<Basico> */
    public function todos(): array;

    public function guardar(Basico $b, ?int $usuarioId = null): Basico;
}
