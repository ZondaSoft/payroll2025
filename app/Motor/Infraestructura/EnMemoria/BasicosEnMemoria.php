<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioBasicos;
use App\Motor\Dominio\Entidades\Basico;
use DateTimeInterface;

final class BasicosEnMemoria implements RepositorioBasicos
{
    use SecuenciaEnMemoria;

    /** @var list<Basico> */
    public array $basicos = [];

    public function porCategorias(array $idsCategoria, DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->basicos, $fecha) as $b) {
            if (in_array($b->idCategoria, $idsCategoria, true)) {
                $out[$b->idCategoria][] = $b;
            }
        }

        return $out;
    }

    public function todos(): array
    {
        return $this->basicos;
    }

    public function guardar(Basico $b, ?int $usuarioId = null): Basico
    {
        $n = new Basico($b->id ?? $this->nuevoId(), $b->idCategoria, $b->codigoCategoria, $b->vigencia, $b->importe, $b->estadoValidacion);
        $this->basicos = $this->reemplazar($this->basicos, $n);

        return $n;
    }
}
