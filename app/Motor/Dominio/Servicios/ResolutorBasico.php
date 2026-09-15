<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\Basico;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\ValorObjetos\Importe;

/** Básico de la categoría vigente: sin importe = bloqueo; más de una escala vigente = bloqueo (mockup resolverBasico). */
final class ResolutorBasico
{
    /** @param array<int, list<Basico>> $basicosPorCategoria vigentes a la fecha */
    public function __construct(private readonly array $basicosPorCategoria, private readonly GateValidacion $gate) {}

    /** @throws MotorBloqueado */
    public function resolver(ContextoRelacionLaboral $ctx): Importe
    {
        if ($ctx->idCategoria === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::BasicoSinImporte, "La relación laboral #{$ctx->relacion->id} no tiene categoría vigente al {$ctx->fechaResolucion->format('Y-m-d')}: el motor bloquea.", 'motor_relacion_laboral_categoria', 'id_categoria');
        }
        $candidatos = $this->basicosPorCategoria[$ctx->idCategoria] ?? [];
        if ($candidatos === []) {
            throw MotorBloqueado::porque(CodigoBloqueo::BasicoSinImporte, "Escala o categoría ({$ctx->codigoCategoria}) sin importe básico vigente al {$ctx->fechaResolucion->format('Y-m-d')}: el motor bloquea.", 'motor_basicos', 'importe_basico');
        }
        if (count($candidatos) > 1) {
            throw MotorBloqueado::porque(CodigoBloqueo::BasicoAmbiguo, "Más de una escala vigente para la categoría {$ctx->codigoCategoria}: el motor bloquea.", 'motor_basicos', 'vigencia_desde');
        }
        $b = $candidatos[0];
        $aviso = $this->gate->evaluar($b->estadoValidacion, 'motor_basicos', "Básico de la categoría {$ctx->codigoCategoria}");
        if ($aviso !== null) {
            $ctx->avisos[] = $aviso;
        }

        return $b->importe;
    }
}
