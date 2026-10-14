<?php

namespace App\Motor\Dominio\Modulos;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Servicios\ResolutorAntiguedad;
use Brick\Math\BigDecimal;

/**
 * MOD(ANTIGUEDAD.PORCENTAJE): el porcentaje por años de servicio según el modelo
 * del convenio.
 *
 * Es el envoltorio del ResolutorAntiguedad que ya existía: la fórmula publicada
 * dice `BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)`, y la mecánica de
 * tramos o progresión lineal queda del lado del módulo, donde se puede cambiar
 * sin tocar el DSL.
 */
final class ModuloAntiguedad implements Modulo
{
    public const CODIGO = 'ANTIGUEDAD.PORCENTAJE';

    public function __construct(private readonly ResolutorAntiguedad $resolutor = new ResolutorAntiguedad) {}

    public function codigo(): string
    {
        return self::CODIGO;
    }

    public function unidad(): string
    {
        return 'PORCENTAJE';
    }

    public function dependencias(): array
    {
        return ['VAR(ANTANIO)'];
    }

    public function resolver(ContextoRelacionLaboral $ctx): BigDecimal
    {
        if ($ctx->modeloAntiguedad === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida,
                'El convenio del trabajador no tiene modelo de antigüedad asignado: el motor bloquea.',
                'motor_convenio_modelo_antiguedad');
        }

        return $this->resolutor->porcentaje($ctx->modeloAntiguedad, $ctx->aniosAntiguedad())->fraccion();
    }
}
