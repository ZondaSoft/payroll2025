<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioAlicuotas;
use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use DateTimeInterface;

final class AlicuotasEnMemoria implements RepositorioAlicuotas
{
    use SecuenciaEnMemoria;

    /** @var list<AlicuotaContribucion> */
    public array $contribuciones = [];

    /** @var list<AlicuotaAporte> */
    public array $aportes = [];

    public function contribucionesVigentes(DateTimeInterface $fecha): array
    {
        return $this->vigentesA_($this->contribuciones, $fecha);
    }

    public function aportesVigentes(DateTimeInterface $fecha): array
    {
        return $this->vigentesA_($this->aportes, $fecha);
    }
}
