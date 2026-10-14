<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\BloqueoDsl;
use App\Motor\Dominio\Formulas\ResolvedorDsl;
use Brick\Math\BigDecimal;

/** Resolvedor en memoria: los valores de un caso concreto, sin base de datos. */
final class ResolvedorDePrueba implements ResolvedorDsl
{
    /**
     * @param  array<string, string>  $parametros
     * @param  array<string, string>  $novedades
     * @param  array<string, string>  $variables
     * @param  array<string, string>  $valcat
     * @param  array<string, string>  $conceptos  importes ya calculados y firmados
     * @param  array<string, string>  $bases
     * @param  array<string, string>  $modulos
     */
    public function __construct(
        private array $parametros = [],
        private array $novedades = [],
        private array $variables = [],
        private array $valcat = [],
        private array $conceptos = [],
        private array $bases = [],
        private array $modulos = [],
    ) {}

    public function parametro(string $codigo): array
    {
        if (! isset($this->parametros[$codigo])) {
            throw BloqueoDsl::porque('PARAM_SIN_VALOR', "Parámetro {$codigo}");
        }

        return ['valor' => BigDecimal::of($this->parametros[$codigo]), 'alcance' => 'CONVENIO', 'vigencia' => '2026-07-01'];
    }

    public function novedad(string $codigo): array
    {
        if (! isset($this->novedades[$codigo])) {
            return ['existe' => false];
        }

        return ['existe' => true, 'valor' => BigDecimal::of($this->novedades[$codigo]), 'unidad' => null];
    }

    public function variable(string $codigo): array
    {
        if (! isset($this->variables[$codigo])) {
            throw BloqueoDsl::porque('VAR_SIN_VALOR', "Variable {$codigo}");
        }

        return ['valor' => BigDecimal::of($this->variables[$codigo])];
    }

    public function valcat(string $codigo): array
    {
        if (! isset($this->valcat[$codigo])) {
            throw BloqueoDsl::porque('VALCAT_SIN_VALOR', "VALCAT {$codigo}");
        }

        return ['valor' => BigDecimal::of($this->valcat[$codigo]), 'categoria' => 104, 'vigencia' => '2026-07-01'];
    }

    public function concepto(string $codigo): BigDecimal
    {
        if (! isset($this->conceptos[$codigo])) {
            throw BloqueoDsl::porque('CONC_BLOQUEADO', "El concepto {$codigo} todavía no fue calculado");
        }

        return BigDecimal::of($this->conceptos[$codigo]);
    }

    public function base(string $codigo): BigDecimal
    {
        if (! isset($this->bases[$codigo])) {
            throw BloqueoDsl::porque('BASE_BLOQUEADA', "La base {$codigo} no fue construida");
        }

        return BigDecimal::of($this->bases[$codigo]);
    }

    public function modulo(string $codigo): array
    {
        if (! isset($this->modulos[$codigo])) {
            throw BloqueoDsl::porque('MOD_SIN_RESULTADO', "Resolvedor {$codigo}");
        }

        return ['valor' => BigDecimal::of($this->modulos[$codigo]), 'version' => 'v1'];
    }
}
