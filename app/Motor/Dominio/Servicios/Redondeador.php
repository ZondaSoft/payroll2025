<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Enums\ReglaRedondeo;
use App\Motor\Dominio\ValorObjetos\Importe;

/**
 * Redondeo por línea de concepto (mockup _redondear). Las bases se construyen
 * SUMANDO líneas ya redondeadas (lo que suma el recibo), sin re-redondear.
 */
final class Redondeador
{
    public function __construct(private readonly int $decimalesParametrizados = 2) {}

    public function redondear(Importe $importe, ?ReglaRedondeo $regla): Importe
    {
        return match ($regla) {
            ReglaRedondeo::Entero => $importe->redondeado(0),
            ReglaRedondeo::Decimal => $importe->redondeado(2),
            ReglaRedondeo::Parametrizada => $importe->redondeado($this->decimalesParametrizados),
            ReglaRedondeo::SinRedondeo, null => $importe,
        };
    }

    /** Decimales efectivos de la regla (null = sin redondeo). */
    public function decimales(?ReglaRedondeo $regla): ?int
    {
        return match ($regla) {
            ReglaRedondeo::Entero => 0,
            ReglaRedondeo::Decimal => 2,
            ReglaRedondeo::Parametrizada => $this->decimalesParametrizados,
            ReglaRedondeo::SinRedondeo, null => null,
        };
    }
}
