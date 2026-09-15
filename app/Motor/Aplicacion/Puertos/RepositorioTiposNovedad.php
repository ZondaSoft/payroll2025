<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\MapeoNovedad;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\Entidades\TipoNovedad;

/** Tipos de novedad del motor y mapeos desde el legacy. */
interface RepositorioTiposNovedad
{
    /** @return list<TipoNovedad> */
    public function todos(): array;

    /** @return array<string, MapeoNovedad> por clave cod_nov|columna */
    public function mapeos(bool $soloActivos = true): array;

    public function guardarMapeo(MapeoNovedad $m, ?int $usuarioId = null): MapeoNovedad;
}
