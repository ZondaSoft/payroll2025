<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use Brick\Math\BigDecimal;

/**
 * Resuelve las variables que se cargan por ámbito: los divisores del convenio
 * (DIVJOR, HMJORN, HSJORN, HDJORN).
 *
 * Misma precedencia y mismo fail-closed que los parámetros: sin valor vigente
 * bloquea, y dos valores del mismo nivel bloquean por ambigüedad. Lo que no se
 * carga acá — BASCAT, HSPACT, DIASLIQ, ANTANIO — lo resuelve el contexto del
 * trabajador, y por eso no tiene sentido buscarlo en esta tabla.
 */
final class ResolutorVariables
{
    /** @var array<string, list<ValorVariable>> */
    private array $porCodigo = [];

    /** @param list<ValorVariable> $valoresVigentes */
    public function __construct(array $valoresVigentes)
    {
        foreach ($valoresVigentes as $v) {
            $this->porCodigo[$v->codigo][] = $v;
        }
    }

    /** @throws MotorBloqueado */
    public function resolver(string $codigo, ContextoRelacionLaboral $ctx): BigDecimal
    {
        $candidatos = array_values(array_filter($this->porCodigo[$codigo] ?? [], fn (ValorVariable $v) => $this->aplica($v, $ctx)));
        if ($candidatos === []) {
            throw MotorBloqueado::porque(CodigoBloqueo::VariableSinValor,
                "Variable '{$codigo}' sin valor vigente para el trabajador al {$ctx->fechaResolucion->format('Y-m-d')}: el motor bloquea.",
                "motor_valores_variable:{$codigo}", 'valor');
        }
        $maximo = max(array_map(fn (ValorVariable $v) => $v->nivel(), $candidatos));
        $ganadores = array_values(array_filter($candidatos, fn (ValorVariable $v) => $v->nivel() === $maximo));
        if (count($ganadores) > 1) {
            throw MotorBloqueado::porque(CodigoBloqueo::VariableSinValor,
                "Empate de la variable '{$codigo}' en el nivel {$ganadores[0]->alcance->value}: el motor bloquea (configuración ambigua).",
                "motor_valores_variable:{$codigo}", 'alcance');
        }

        return $ganadores[0]->valor;
    }

    public function existe(string $codigo, ContextoRelacionLaboral $ctx): bool
    {
        foreach ($this->porCodigo[$codigo] ?? [] as $v) {
            if ($this->aplica($v, $ctx)) {
                return true;
            }
        }

        return false;
    }

    private function aplica(ValorVariable $v, ContextoRelacionLaboral $ctx): bool
    {
        if (! $v->vigencia->contiene($ctx->fechaResolucion)) {
            return false;
        }

        return match ($v->alcance->value) {
            'GENERAL' => true,
            'CONVENIO' => $v->idConvenio !== null && $v->idConvenio === $ctx->idConvenio,
            'CATEGORIA' => $v->idCategoria !== null && $v->idCategoria === $ctx->idCategoria,
            'TRABAJADOR' => $v->relacionLaboralId !== null && $v->relacionLaboralId === $ctx->relacion->id,
            default => false,
        };
    }
}
