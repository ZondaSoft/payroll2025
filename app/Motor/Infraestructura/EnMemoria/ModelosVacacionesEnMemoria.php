<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioModelosVacaciones;
use App\Motor\Dominio\Entidades\AsignacionModeloConvenio;
use App\Motor\Dominio\Entidades\ModeloVacaciones;
use DateTimeInterface;

final class ModelosVacacionesEnMemoria implements RepositorioModelosVacaciones
{
    use SecuenciaEnMemoria;

    /** @var list<ModeloVacaciones> */
    public array $modelos = [];

    /** @var list<AsignacionModeloConvenio> */
    public array $asignaciones = [];

    public function asignadosAConvenios(array $idsConvenio, DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->asignaciones, $fecha) as $a) {
            if (! in_array($a->idConvenio, $idsConvenio, true)) {
                continue;
            }
            foreach ($this->modelos as $m) {
                if ($m->id === $a->idModelo) {
                    $out[$a->idConvenio] = $m;
                }
            }
        }

        return $out;
    }

    public function todos(): array
    {
        return $this->modelos;
    }
}
