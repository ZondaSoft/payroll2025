<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;

/** Lo que produce evaluar un concepto: su estado, el importe firmado y la traza. */
final readonly class ResultadoConcepto
{
    public const CALCULADO = 'CALCULADO';

    public const NO_APLICA = 'NO_APLICA';

    public const BLOQUEADO = 'BLOQUEADO';

    public const NO_MODELADO = 'NO_MODELADO';

    private function __construct(
        public string $codigoConcepto,
        public string $estado,
        public ?BigDecimal $importeBruto = null,
        public ?BigDecimal $importeFormula = null,
        public ?BigDecimal $importeResultado = null,
        public int $factorSigno = 1,
        public bool $impactaNeto = true,
        public ?BigDecimal $cantidad = null,
        public ?BigDecimal $valorUnitario = null,
        public ?string $codigoError = null,
        public ?string $motivo = null,
        public ?string $variante = null,
        public ?TrazaFormula $traza = null,
    ) {}

    public static function calculado(
        string $codigo, BigDecimal $bruto, BigDecimal $formula, int $factor, bool $impactaNeto,
        ?BigDecimal $cantidad, ?BigDecimal $valorUnitario, ?string $variante, TrazaFormula $traza
    ): self {
        return new self($codigo, self::CALCULADO, $bruto, $formula, $formula->multipliedBy($factor),
            $factor, $impactaNeto, $cantidad, $valorUnitario, null, null, $variante, $traza);
    }

    public static function noAplica(string $codigo, string $motivo, ?string $variante = null, ?TrazaFormula $traza = null): self
    {
        return new self($codigo, self::NO_APLICA, motivo: $motivo, variante: $variante, traza: $traza);
    }

    public static function bloqueado(string $codigo, string $codigoError, string $motivo, ?string $variante = null, ?TrazaFormula $traza = null): self
    {
        return new self($codigo, self::BLOQUEADO, codigoError: $codigoError, motivo: $motivo, variante: $variante, traza: $traza);
    }

    public static function noModelado(string $codigo, string $pendiente): self
    {
        return new self($codigo, self::NO_MODELADO, codigoError: 'PENDIENTE_'.$pendiente,
            motivo: "El concepto corresponde al trabajador pero su regla todavía no está definida ({$pendiente})");
    }

    public function esCalculado(): bool
    {
        return $this->estado === self::CALCULADO;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'concepto' => $this->codigoConcepto, 'estado' => $this->estado, 'variante' => $this->variante,
            'cantidad' => $this->cantidad === null ? null : (string) $this->cantidad,
            'valor_unitario' => $this->valorUnitario === null ? null : (string) $this->valorUnitario,
            'importe_bruto' => $this->importeBruto === null ? null : (string) $this->importeBruto,
            'importe_resultado' => $this->importeResultado === null ? null : (string) $this->importeResultado,
            'factor_signo' => $this->factorSigno, 'impacta_neto' => $this->impactaNeto,
            'codigo_error' => $this->codigoError, 'motivo' => $this->motivo,
            'referencias' => $this->traza?->resumen() ?? [],
        ];
    }
}
