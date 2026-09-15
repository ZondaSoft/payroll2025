<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Enums\TipoAmbito;

final class AplicabilidadEnMemoria implements RepositorioAplicabilidad
{
    use SecuenciaEnMemoria;

    /** @var list<ReglaAplicabilidad> */
    public array $reglas = [];

    public function deConcepto(string $codigoConcepto): array
    {
        return array_values(array_filter($this->reglas, fn ($r) => $r->codigoConcepto === $codigoConcepto));
    }

    public function guardar(ReglaAplicabilidad $r, ?int $usuarioId = null): ReglaAplicabilidad
    {
        $n = new ReglaAplicabilidad($r->id ?? $this->nuevoId(), $r->codigoConcepto, $r->ambito, $r->idConvenio, $r->idCategoria, $r->idEmpresa, $r->relacionLaboralId,
            $r->accion, $r->vigencia, $r->estadoValidacion, $r->ambito === TipoAmbito::Categoria ? 'CATEGORIA_CONCEPTO' : 'APLICABILIDAD');
        $this->reglas = $this->reemplazar($this->reglas, $n);

        return $n;
    }

    public function eliminar(int $id, string $origen): void
    {
        $this->reglas = array_values(array_filter($this->reglas, fn ($r) => $r->id !== $id));
    }
}
