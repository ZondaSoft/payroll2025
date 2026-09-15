<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\ValorObjetos\Importe;

/** Una base (Zonda o ARCA REM_n) construida a partir de líneas ya redondeadas, con trazabilidad y ajustes de tope. */
final class BaseImponibleResultado
{
    /** @var array<string, string> codigo_concepto => importe */
    public array $incluidos = [];

    /** @var array<string, string> codigo_concepto => motivo */
    public array $excluidos = [];

    public Importe $importeAntesAjustes;

    public Importe $ajusteTope;

    public Importe $importeAplicado;

    public ?string $topeAplicado = null;

    public ?string $tipoTopeAplicado = null;

    public function __construct(public readonly string $codigoBase, public readonly string $componente = 'MENSUAL')
    {
        $this->importeAntesAjustes = Importe::cero();
        $this->ajusteTope = Importe::cero();
        $this->importeAplicado = Importe::cero();
    }

    public function incluir(string $codigoConcepto, Importe $importe): void
    {
        $this->incluidos[$codigoConcepto] = $importe->aTexto(2);
        $this->importeAntesAjustes = $this->importeAntesAjustes->mas($importe);
        $this->importeAplicado = $this->importeAntesAjustes;
    }

    public function excluir(string $codigoConcepto, string $motivo): void
    {
        $this->excluidos[$codigoConcepto] = $motivo;
    }

    public function aplicarTope(Importe $importeTopeado, string $codigoTope, string $tipo): void
    {
        $this->ajusteTope = $importeTopeado->menos($this->importeAntesAjustes);
        $this->importeAplicado = $importeTopeado;
        $this->topeAplicado = $codigoTope;
        $this->tipoTopeAplicado = $tipo;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo_base' => $this->codigoBase, 'componente' => $this->componente,
            'importe_antes_ajustes' => $this->importeAntesAjustes->aTexto(2), 'ajuste_tope' => $this->ajusteTope->aTexto(2),
            'importe_aplicado' => $this->importeAplicado->aTexto(2), 'tope_aplicado' => $this->topeAplicado, 'tipo_tope' => $this->tipoTopeAplicado,
            'incluidos' => $this->incluidos, 'excluidos' => $this->excluidos,
        ];
    }
}
