<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\DTO\AmbitosLote;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Dominio\Entidades\Parametro;
use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\AlcanceParametro;
use DateTimeInterface;

final class ParametrosEnMemoria implements RepositorioParametros
{
    use SecuenciaEnMemoria;

    /** @var list<Parametro> */
    public array $parametros = [];

    /** @var list<ValorParametro> */
    public array $valores = [];

    public function todos(): array
    {
        return $this->parametros;
    }

    public function porCodigo(string $codigo): ?Parametro
    {
        foreach ($this->parametros as $p) {
            if ($p->codigo === $codigo) {
                return $p;
            }
        }

        return null;
    }

    public function valoresVigentes(DateTimeInterface $fecha, AmbitosLote $ambitos): array
    {
        return array_values(array_filter($this->vigentesA_($this->valores, $fecha), fn (ValorParametro $v) => match ($v->alcance) {
            AlcanceParametro::General => true,
            AlcanceParametro::Convenio => in_array($v->idConvenio, $ambitos->idsConvenio, true),
            AlcanceParametro::Categoria => in_array($v->idCategoria, $ambitos->idsCategoria, true),
            AlcanceParametro::Empresa => in_array($v->idEmpresa, $ambitos->idsEmpresa, true),
            AlcanceParametro::Trabajador => in_array($v->relacionLaboralId, $ambitos->idsRelacionLaboral, true),
        }));
    }

    public function valoresDe(int $idParametro): array
    {
        return array_values(array_filter($this->valores, fn ($v) => $v->idParametro === $idParametro));
    }

    public function guardarValor(ValorParametro $v, ?int $usuarioId = null): ValorParametro
    {
        $n = new ValorParametro($v->id ?? $this->nuevoId(), $v->idParametro, $v->codigoParametro, $v->alcance, $v->idConvenio, $v->idCategoria, $v->idEmpresa, $v->relacionLaboralId, $v->valor, $v->vigencia, $v->estadoValidacion);
        $this->valores = $this->reemplazar($this->valores, $n);

        return $n;
    }
}
