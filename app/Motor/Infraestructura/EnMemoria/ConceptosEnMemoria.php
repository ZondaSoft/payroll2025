<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;

final class ConceptosEnMemoria implements RepositorioConceptos
{
    use SecuenciaEnMemoria;

    /** @var list<Concepto> */
    public array $conceptos = [];

    public function todos(bool $soloActivos = false): array
    {
        return $soloActivos ? array_values(array_filter($this->conceptos, fn ($c) => $c->activo)) : $this->conceptos;
    }

    public function porCodigo(CodigoConcepto $codigo): ?Concepto
    {
        foreach ($this->conceptos as $c) {
            if ($c->codigo->igualA($codigo)) {
                return $c;
            }
        }

        return null;
    }

    public function porId(int $id): ?Concepto
    {
        foreach ($this->conceptos as $c) {
            if ($c->id === $id) {
                return $c;
            }
        }

        return null;
    }

    public function guardar(Concepto $c, ?int $usuarioId = null): Concepto
    {
        $n = Concepto::desdeDefinicion($c->aDefinicion(), $c->id ?? $this->nuevoId(), $c->activo, $c->estadoConfiguracion, $c->ordenVisual, $c->observaciones);
        $this->conceptos = $this->reemplazar($this->conceptos, $n);

        return $n;
    }

    public function bloquearParaPublicar(int $idConcepto): void {}

    public function cambiarEstadoConfiguracion(int $idConcepto, string $estado): void
    {
        $this->modificar($idConcepto, fn (Concepto $c) => Concepto::desdeDefinicion($c->aDefinicion(), $c->id, $c->activo, EstadoConfiguracion::from($estado), $c->ordenVisual, $c->observaciones));
    }

    public function darDeBaja(int $idConcepto): void
    {
        $this->modificar($idConcepto, fn (Concepto $c) => Concepto::desdeDefinicion($c->aDefinicion(), $c->id, false, $c->estadoConfiguracion, $c->ordenVisual, $c->observaciones));
    }

    private function modificar(int $id, callable $fn): void
    {
        foreach ($this->conceptos as $k => $c) {
            if ($c->id === $id) {
                $this->conceptos[$k] = $fn($c);
            }
        }
    }
}
