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

    /** Diferencial ajeno a la matriz de conceptos (p. ej. obra social por jornada parcial). */
    public Importe $importeDiferencial;

    /** @var array<string, string> codigo de regla => importe del diferencial que aportó */
    public array $diferenciales = [];

    /** MATRIZ: sale de las incidencias de conceptos. DERIVADA: se calcula desde otra base (REM_10). */
    public string $origenCalculo = 'MATRIZ';

    public ?string $detalleDerivacion = null;

    public function __construct(public readonly string $codigoBase, public readonly string $componente = 'MENSUAL')
    {
        $this->importeAntesAjustes = Importe::cero();
        $this->ajusteTope = Importe::cero();
        $this->importeAplicado = Importe::cero();
        $this->importeDiferencial = Importe::cero();
    }

    public function incluir(string $codigoConcepto, Importe $importe): void
    {
        $this->incluidos[$codigoConcepto] = $importe->aTexto(2);
        $this->importeAntesAjustes = $this->importeAntesAjustes->mas($importe);
        $this->importeAplicado = $this->importeAntesAjustes;
    }

    /**
     * Suma un diferencial de base que no proviene de la matriz de conceptos.
     * Entra ANTES de los topes: la base topeable es bruto + diferencial.
     */
    public function agregarDiferencial(Importe $importe, string $codigoRegla): void
    {
        $this->importeDiferencial = $this->importeDiferencial->mas($importe);
        $this->diferenciales[$codigoRegla] = $importe->aTexto(2);
        $this->importeAntesAjustes = $this->importeAntesAjustes->mas($importe);
        $this->importeAplicado = $this->importeAntesAjustes;
    }

    /** Base que no se compone de conceptos sino que se deriva de otra (REM_10 = REM_2 - detracción). */
    public function derivar(Importe $importe, string $detalle): void
    {
        $this->origenCalculo = 'DERIVADA';
        $this->detalleDerivacion = $detalle;
        $this->importeAntesAjustes = $importe;
        $this->importeAplicado = $importe;
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
            'origen_calculo' => $this->origenCalculo, 'detalle_derivacion' => $this->detalleDerivacion,
            'importe_antes_ajustes' => $this->importeAntesAjustes->aTexto(2), 'importe_diferencial' => $this->importeDiferencial->aTexto(2),
            'ajuste_tope' => $this->ajusteTope->aTexto(2),
            'importe_aplicado' => $this->importeAplicado->aTexto(2), 'tope_aplicado' => $this->topeAplicado, 'tipo_tope' => $this->tipoTopeAplicado,
            'incluidos' => $this->incluidos, 'excluidos' => $this->excluidos, 'diferenciales' => $this->diferenciales,
        ];
    }
}
