<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\BaseImponibleResultado;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;

/**
 * Bases de Zonda: suma de las líneas ya redondeadas de los conceptos que la
 * integran (según el snapshot de cada concepto), con trazabilidad de incluidos/excluidos.
 */
final class ConstructorBasesZonda
{
    /**
     * @param  array<string, list<string>>  $integrantes  base => conceptos que la integran (vigentes)
     * @param  list<LineaHaber>  $haberes
     */
    public function construir(string $codigoBase, array $integrantes, array $haberes): BaseImponibleResultado
    {
        $r = new BaseImponibleResultado($codigoBase, 'ZONDA');
        $conceptos = $integrantes[$codigoBase] ?? null;
        if ($conceptos === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::BaseZondaInexistente, "La base {$codigoBase} no existe, no está activa o no tiene composición vigente.", 'motor_bases_zonda');
        }
        foreach ($haberes as $h) {
            if (in_array($h->codigo(), $conceptos, true)) {
                $r->incluir($h->codigo(), $h->importe);
            } else {
                $r->excluir($h->codigo(), 'no integra la base');
            }
        }

        return $r;
    }
}
