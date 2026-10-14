<?php

namespace App\Motor\Dominio\Formulas;

/**
 * La versión de un concepto, vista por el DSL: lo que hace falta para validarla
 * y ejecutarla. No es la entidad ConceptoVersion (que además carga snapshot,
 * hash y auditoría): es su parte ejecutable, tal como la guarda
 * motor_concepto_versiones en las columnas expr_*.
 */
final readonly class VersionFormula
{
    public const FORMULA = 'FORMULA';

    public const MODULO = 'MODULO';

    /**
     * @param  array<string, string>  $novedades  codigo => REQUERIDA | OPCIONAL (motor_concepto_version_novedad)
     * @param  array<string, string>  $justificaciones  literal => justificación (motor_concepto_version_literal)
     */
    public function __construct(
        public string $codigoConcepto,
        public string $variante = 'UNICA',
        public string $metodo = self::FORMULA,
        public ?string $exprCondicion = null,
        public ?string $exprCantidad = null,
        public ?string $exprValorUnitario = null,
        public ?string $exprImporte = null,
        public ?string $unidadCantidad = null,
        public ?string $codigoModulo = null,
        public string $reglaRedondeo = 'SIN_REDONDEO',
        public string $signo = 'CREDITO',
        public array $novedades = [],
        public array $justificaciones = [],
        public ?int $id = null,
    ) {}

    public function esModulo(): bool
    {
        return $this->metodo === self::MODULO;
    }

    public function tieneCantidad(): bool
    {
        return trim((string) $this->exprCantidad) !== '';
    }

    public function tieneValor(): bool
    {
        return trim((string) $this->exprValorUnitario) !== '';
    }

    public function tieneCondicion(): bool
    {
        return trim((string) $this->exprCondicion) !== '';
    }

    /** NEUTRO cuenta como +1 pero no impacta el neto (§17). */
    public function factorSigno(): int
    {
        return $this->signo === 'DEBITO' ? -1 : 1;
    }

    public function impactaNeto(): bool
    {
        return $this->signo !== 'NEUTRO';
    }

    /** @return list<string> novedades cuya ausencia deja el concepto NO_APLICA */
    public function novedadesRequeridas(): array
    {
        return array_keys(array_filter($this->novedades, fn (string $o) => $o === 'REQUERIDA'));
    }
}
