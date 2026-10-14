<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;

/**
 * Lo que el evaluador le pregunta al mundo cuando ejecuta una fórmula. Cada
 * método devuelve el valor resuelto o lanza BloqueoDsl: nunca devuelve cero
 * para tapar un dato que falta.
 *
 * La excepción deliberada es `novedad()`, que informa si existe: una novedad
 * declarada OPCIONAL y ausente vale cero tipado, y una REQUERIDA ausente deja
 * el concepto NO_APLICA — esa decisión es de la capa que orquesta, no de acá.
 */
interface ResolvedorDsl
{
    /** @return array{valor: BigDecimal, alcance?: ?string, vigencia?: ?string} */
    public function parametro(string $codigo): array;

    /** @return array{existe: bool, valor?: BigDecimal, unidad?: ?string} */
    public function novedad(string $codigo): array;

    /** @return array{valor: BigDecimal} */
    public function variable(string $codigo): array;

    /** @return array{valor: BigDecimal, categoria?: ?int, vigencia?: ?string} */
    public function valcat(string $codigo): array;

    /** Importe ya firmado del concepto calculado (§17). */
    public function concepto(string $codigo): BigDecimal;

    public function base(string $codigo): BigDecimal;

    /** @return array{valor: BigDecimal, version?: ?string} */
    public function modulo(string $codigo): array;
}
