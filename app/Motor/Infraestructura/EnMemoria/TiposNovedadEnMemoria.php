<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Dominio\Entidades\MapeoNovedad;
use App\Motor\Dominio\Entidades\TipoNovedad;

final class TiposNovedadEnMemoria implements RepositorioTiposNovedad
{
    use SecuenciaEnMemoria;

    /** @var list<TipoNovedad> */
    public array $tipos = [];

    /** @var array<string, MapeoNovedad> */
    public array $mapeosNovedad = [];

    public function todos(): array
    {
        return $this->tipos;
    }

    public function mapeos(bool $soloActivos = true): array
    {
        return $soloActivos ? array_filter($this->mapeosNovedad, fn ($m) => $m->activo) : $this->mapeosNovedad;
    }

    public function guardarMapeo(MapeoNovedad $m, ?int $usuarioId = null): MapeoNovedad
    {
        $n = new MapeoNovedad($m->id ?? $this->nuevoId(), $m->codNov, $m->columnaOrigen, $m->codigoTipoNovedad, $m->unidad, $m->esImporte, $m->factor, $m->accion, $m->activo);
        $this->mapeosNovedad[$n->clave()] = $n;

        return $n;
    }
}
