<?php

namespace App\Motor\Infraestructura\EnMemoria;

use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use DateTimeInterface;

final class VersionesEnMemoria implements RepositorioVersiones
{
    use SecuenciaEnMemoria;

    /** @var list<ConceptoVersion> */
    public array $items = [];

    public function __construct(private readonly ?ConceptosEnMemoria $conceptos = null) {}

    public function vigentesA(DateTimeInterface $fecha): array
    {
        $out = [];
        foreach ($this->vigentesA_($this->items, $fecha) as $v) {
            $out[$v->codigoConcepto] = $v;
        }

        return $out;
    }

    public function deConcepto(int $idConcepto): array
    {
        $v = array_values(array_filter($this->items, fn ($x) => $x->idConcepto === $idConcepto));
        usort($v, fn ($a, $b) => $a->vigencia->desde <=> $b->vigencia->desde);

        return $v;
    }

    public function porId(int $idVersion): ?ConceptoVersion
    {
        foreach ($this->items as $v) {
            if ($v->id === $idVersion) {
                return $v;
            }
        }

        return null;
    }

    public function guardar(ConceptoVersion $v): ConceptoVersion
    {
        $n = new ConceptoVersion($v->id ?? $this->nuevoId(), $v->idConcepto, $v->codigoConcepto, $v->numeroVersion, $v->vigencia, $v->snapshot, $v->hash,
            $v->esquema, $v->motivo, $v->tipoCambio, $v->usadaEnLiquidacion, $v->publicadaAt, $v->usuarioId);
        $this->items = $this->reemplazar($this->items, $n);

        return $n;
    }

    public function cerrarVigencia(int $idVersion, DateTimeInterface $hasta): void
    {
        foreach ($this->items as $k => $v) {
            if ($v->id === $idVersion) {
                $this->items[$k] = $v->conVigencia(new Vigencia($v->vigencia->desde, $hasta));
            }
        }
    }

    public function marcarUsadas(array $idsVersion): void
    {
        foreach ($this->items as $k => $v) {
            if (in_array($v->id, $idsVersion, true)) {
                $this->items[$k] = new ConceptoVersion($v->id, $v->idConcepto, $v->codigoConcepto, $v->numeroVersion, $v->vigencia, $v->snapshot, $v->hash, $v->esquema, $v->motivo, $v->tipoCambio, true, $v->publicadaAt, $v->usuarioId);
            }
        }
    }

    public function conceptosSinVersion(): array
    {
        $con = array_map(fn ($v) => $v->idConcepto, $this->items);
        $conceptos = $this->conceptos?->todos(true) ?? [];

        return array_values(array_map(fn ($c) => $c->id, array_filter($conceptos, fn ($c) => $c->estaPublicado() && ! in_array($c->id, $con, true))));
    }
}
