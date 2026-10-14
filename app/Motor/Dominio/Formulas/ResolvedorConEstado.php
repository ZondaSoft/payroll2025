<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;

/**
 * Resuelve CONC() y BASE() desde el estado de la corrida y delega el resto en el
 * resolvedor de datos. Así el evaluador no conoce el orden de cálculo: pregunta y,
 * si el concepto del que depende todavía no se resolvió, bloquea.
 */
final class ResolvedorConEstado implements ResolvedorDsl
{
    public function __construct(
        private readonly ResolvedorDsl $datos,
        private readonly EstadoCorrida $estado,
    ) {}

    public function parametro(string $codigo): array
    {
        return $this->datos->parametro($codigo);
    }

    public function novedad(string $codigo): array
    {
        return $this->datos->novedad($codigo);
    }

    public function variable(string $codigo): array
    {
        return $this->datos->variable($codigo);
    }

    public function valcat(string $codigo): array
    {
        return $this->datos->valcat($codigo);
    }

    public function concepto(string $codigo): BigDecimal
    {
        $r = $this->estado->concepto($codigo);
        if ($r === null) {
            throw BloqueoDsl::porque('CONC_BLOQUEADO', "El concepto {$codigo} todavía no fue calculado");
        }
        // Que una dependencia directa no aplique es distinto de que esté bloqueada:
        // el contrato las distingue porque la causa y el remedio son distintos.
        if ($r->estado === ResultadoConcepto::NO_APLICA) {
            throw BloqueoDsl::porque('DEPENDENCIA_NO_APLICABLE', "El concepto {$codigo} no aplica a este trabajador");
        }
        if (! $r->esCalculado()) {
            throw BloqueoDsl::porque('CONC_BLOQUEADO', "El concepto {$codigo} está bloqueado ({$r->codigoError})");
        }

        return $r->importeResultado;
    }

    public function base(string $codigo): BigDecimal
    {
        $b = $this->estado->base($codigo);
        if ($b === null) {
            throw BloqueoDsl::porque('BASE_BLOQUEADA', "La base {$codigo} no fue construida");
        }
        if (! $b->esOk()) {
            throw BloqueoDsl::porque('BASE_BLOQUEADA', "La base {$codigo} está bloqueada: {$b->motivo}");
        }

        return $b->importe;
    }

    public function modulo(string $codigo): array
    {
        return $this->datos->modulo($codigo);
    }
}
