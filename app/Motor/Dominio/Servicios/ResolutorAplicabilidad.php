<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;

/**
 * Decide si un concepto aplica al trabajador (mockup resolverAplicabilidad):
 * gana la regla de mayor nivel (TRABAJADOR > EMPRESA > CATEGORIA > CONVENIO > GENERAL);
 * si en el nivel ganador conviven INCLUIR y EXCLUIR → CONFLICTO (bloqueo fatal);
 * sin regla coincidente → no aplica (SIN_REGLA).
 */
final class ResolutorAplicabilidad
{
    public const INCLUIDO = 'INCLUIDO';

    public const EXCLUIDO = 'EXCLUIDO';

    public const SIN_REGLA = 'SIN_REGLA';

    /**
     * @param  list<ReglaAplicabilidad>  $reglas  reglas del concepto (del snapshot)
     * @return array{resultado: string, aplica: bool, nivel: ?int, regla: ?ReglaAplicabilidad}
     */
    public function decidir(string $codigoConcepto, array $reglas, ContextoRelacionLaboral $ctx): array
    {
        $fecha = $ctx->fechaResolucion;
        $candidatas = array_values(array_filter($reglas, fn (ReglaAplicabilidad $r) => $r->vigencia->contiene($fecha) && $this->coincide($r, $ctx)));
        if ($candidatas === []) {
            return ['resultado' => self::SIN_REGLA, 'aplica' => false, 'nivel' => null, 'regla' => null];
        }
        $maximo = max(array_map(fn (ReglaAplicabilidad $r) => $r->nivel(), $candidatas));
        $top = array_values(array_filter($candidatas, fn (ReglaAplicabilidad $r) => $r->nivel() === $maximo));
        $acciones = array_unique(array_map(fn (ReglaAplicabilidad $r) => $r->accion->value, $top));
        if (count($acciones) > 1) {
            throw MotorBloqueado::fatal(CodigoBloqueo::ConflictoPerfiles, "Conflicto de aplicabilidad para {$codigoConcepto}: INCLUIR y EXCLUIR en el mismo nivel ({$top[0]->ambito->value}). El motor bloquea.", "motor_concepto_aplicabilidad:{$codigoConcepto}");
        }
        $gana = $top[0];
        $incluye = $gana->accion === AccionAplicabilidad::Incluir;

        return ['resultado' => $incluye ? self::INCLUIDO : self::EXCLUIDO, 'aplica' => $incluye, 'nivel' => $maximo, 'regla' => $gana];
    }

    private function coincide(ReglaAplicabilidad $r, ContextoRelacionLaboral $ctx): bool
    {
        return match ($r->ambito) {
            TipoAmbito::General => true,
            TipoAmbito::Convenio => $r->idConvenio !== null && $r->idConvenio === $ctx->idConvenio,
            TipoAmbito::Categoria => $r->idCategoria !== null && $r->idCategoria === $ctx->idCategoria,
            TipoAmbito::Empresa => $r->idEmpresa !== null && $r->idEmpresa === $ctx->relacion->empresaId,
            TipoAmbito::Trabajador => $r->relacionLaboralId !== null && $r->relacionLaboralId === $ctx->relacion->id,
        };
    }
}
