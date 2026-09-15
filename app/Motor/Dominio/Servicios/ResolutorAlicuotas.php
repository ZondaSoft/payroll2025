<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;

/**
 * Alícuota de aporte por subsistema y condición ARCA del trabajador:
 * una fila específica (condición = la del trabajador) siempre gana sobre el comodín (NULL).
 */
final class ResolutorAlicuotas
{
    /** @param list<AlicuotaAporte> $aportesVigentes */
    public function __construct(private readonly array $aportesVigentes, private readonly GateValidacion $gate) {}

    /** @return array{alicuota: AlicuotaAporte, aviso: ?Aviso} */
    public function aporte(string $codigoSubsistema, string $codigoCondicion): array
    {
        $especifica = null;
        $comodin = null;
        foreach ($this->aportesVigentes as $a) {
            if ($a->codigoSubsistema !== $codigoSubsistema) {
                continue;
            }
            if ($a->codigoCondicion === $codigoCondicion) {
                if ($especifica !== null) {
                    throw MotorBloqueado::porque(CodigoBloqueo::AlicuotaAusente, "Más de una alícuota de aporte vigente para {$codigoSubsistema} / condición {$codigoCondicion}: configuración ambigua.", 'motor_alicuotas_aportes');
                }
                $especifica = $a;
            } elseif ($a->esComodin()) {
                if ($comodin !== null) {
                    throw MotorBloqueado::porque(CodigoBloqueo::AlicuotaAusente, "Más de una alícuota de aporte comodín vigente para {$codigoSubsistema}: configuración ambigua.", 'motor_alicuotas_aportes');
                }
                $comodin = $a;
            }
        }
        $a = $especifica ?? $comodin;
        if ($a === null) {
            throw MotorBloqueado::porque(CodigoBloqueo::AlicuotaAusente, "Sin alícuota de aporte vigente para {$codigoSubsistema} (condición {$codigoCondicion} ni comodín).", 'motor_alicuotas_aportes');
        }
        $aviso = $this->gate->evaluar($a->estadoValidacion, "motor_alicuotas_aportes:{$a->codigo}", "Alícuota de aporte {$a->codigo} ({$codigoSubsistema})");

        return ['alicuota' => $a, 'aviso' => $aviso];
    }

    /** @return list<string> subsistemas con aporte vigente */
    public function subsistemasConAporte(): array
    {
        return array_values(array_unique(array_map(fn (AlicuotaAporte $a) => $a->codigoSubsistema, $this->aportesVigentes)));
    }
}
