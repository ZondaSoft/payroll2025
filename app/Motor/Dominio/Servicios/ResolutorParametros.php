<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\Resultado\Traza;
use Brick\Math\BigDecimal;

/**
 * Resuelve el valor de un parámetro para un trabajador: precedencia
 * TRABAJADOR(5) > EMPRESA(4) > CATEGORIA(3) > CONVENIO(2) > GENERAL(1).
 * Dos valores vigentes del mismo nivel = empate = bloqueo. Sin valor = bloqueo.
 * (mockup resolverParametro; fail-closed.)
 */
final class ResolutorParametros
{
    /** @var array<string, list<ValorParametro>> codigo => valores vigentes (todos los ámbitos del lote) */
    private array $porCodigo = [];

    /** @param list<ValorParametro> $valoresVigentes */
    public function __construct(array $valoresVigentes, private readonly GateValidacion $gate)
    {
        foreach ($valoresVigentes as $v) {
            $this->porCodigo[$v->codigoParametro][] = $v;
        }
    }

    /** @throws MotorBloqueado */
    public function resolver(string $codigo, ContextoRelacionLaboral $ctx, ?Traza $traza = null, ?string $concepto = null): BigDecimal
    {
        $candidatos = array_values(array_filter($this->porCodigo[$codigo] ?? [], fn (ValorParametro $v) => $this->aplica($v, $ctx)));
        if ($candidatos === []) {
            throw MotorBloqueado::porque(CodigoBloqueo::ParametroSinValor, "Parámetro obligatorio '{$codigo}' sin valor vigente para el trabajador al {$ctx->fechaResolucion->format('Y-m-d')}: el motor bloquea.", "motor_parametros:{$codigo}", 'valor');
        }
        $maximo = max(array_map(fn (ValorParametro $v) => $v->nivel(), $candidatos));
        $ganadores = array_values(array_filter($candidatos, fn (ValorParametro $v) => $v->nivel() === $maximo));
        if (count($ganadores) > 1) {
            throw MotorBloqueado::porque(CodigoBloqueo::ParametroEmpate, "Empate de parámetro '{$codigo}' en el nivel {$ganadores[0]->alcance->value}: el motor bloquea (configuración ambigua).", "motor_parametros:{$codigo}", 'alcance');
        }
        $v = $ganadores[0];
        $aviso = $this->gate->evaluar($v->estadoValidacion, "motor_valores_parametros:{$codigo}", "Parámetro {$codigo} ({$v->alcance->value})", true, $concepto);
        if ($aviso !== null) {
            $ctx->avisos[] = $aviso;
        }
        $traza?->parametroResuelto($codigo, ['valor' => (string) $v->valor, 'alcance' => $v->alcance->value, 'vigencia' => (string) $v->vigencia, 'estado' => $v->estadoValidacion->value,
            'ambito' => $v->idConvenio ?? $v->idCategoria ?? $v->idEmpresa ?? $v->relacionLaboralId]);

        return $v->valor;
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

    private function aplica(ValorParametro $v, ContextoRelacionLaboral $ctx): bool
    {
        return match ($v->alcance->value) {
            'GENERAL' => true,
            'CONVENIO' => $v->idConvenio !== null && $v->idConvenio === $ctx->idConvenio,
            'CATEGORIA' => $v->idCategoria !== null && $v->idCategoria === $ctx->idCategoria,
            'EMPRESA' => $v->idEmpresa !== null && $v->idEmpresa === $ctx->relacion->empresaId,
            'TRABAJADOR' => $v->relacionLaboralId !== null && $v->relacionLaboralId === $ctx->relacion->id,
            default => false,
        };
    }
}
