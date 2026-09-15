<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use App\Motor\Dominio\Entidades\RegimenContributivo;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Dominio\Enums\TipoRegla;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use DateTimeImmutable;

/**
 * Contribuciones patronales por subsistema (hoja N1 + REGIMENES_CONTRIBUTIVOS):
 *  1. encuadramiento A/B de la empresa → alícuota BASE por subsistema (ALICUOTAS_CONTRIBUCIONES)
 *  2. regímenes del trabajador por orden_aplicacion:
 *     BASE (10) punto de partida · SUSTITUTIVO (20) reemplaza la alícuota base completa
 *     ADICIONAL (30) suma sobre la misma base · DESTINO (40) redirige sin sumar
 *  "Si el motor sumara todos los porcentajes en vez de distinguir la operación,
 *   un chofer con RIFL pagaría 25 % en lugar de 7 %."
 *  RIFL (710): 5 % por 48 meses desde el ingreso, sustituye SIPA+INSSJP+AAFF+FNE; OS y LRT quedan aparte.
 */
final class ResolutorRegimenes
{
    /**
     * @param  list<AlicuotaContribucion>  $alicuotasContribuciones  vigentes
     * @param  list<RegimenContributivo>  $regimenesVigentes
     */
    public function __construct(private readonly array $alicuotasContribuciones, private readonly array $regimenesVigentes, private readonly GateValidacion $gate) {}

    /**
     * @param  list<string>  $codigosRegimenesTrabajador  regímenes asignados vigentes (p. ej. ['GENERAL','TRANSP_CARGAS'])
     * @return array{lineas: list<array{subsistema: string, base: string, porcentaje: Porcentaje, regla: string, regimen: string, destino: ?string}>, avisos: list<Aviso>}
     */
    public function contribuciones(string $encuadramiento, array $codigosRegimenesTrabajador, Periodo $periodo, DateTimeImmutable $fechaIngreso): array
    {
        $avisos = [];
        $regimenes = [];
        foreach ($this->regimenesVigentes as $r) {
            if (in_array($r->codigo, $codigosRegimenesTrabajador, true)) {
                $regimenes[] = $r;
            }
        }
        usort($regimenes, fn ($a, $b) => $a->ordenAplicacion <=> $b->ordenAplicacion);

        $base = array_values(array_filter($regimenes, fn ($r) => $r->tipoRegla === TipoRegla::Base && $r->sujetoObligado === SujetoObligado::Empleador));
        if (count($base) !== 1) {
            throw MotorBloqueado::porque(CodigoBloqueo::RegimenSinBase, count($base) === 0
                ? 'El trabajador no tiene asignado un régimen BASE (GENERAL): siempre debe haber exactamente uno.'
                : 'El trabajador tiene más de un régimen BASE asignado: configuración ambigua.', 'motor_relacion_laboral_regimen');
        }
        $avisoBase = $this->gate->evaluar($base[0]->estadoValidacion, "motor_regimenes_contributivos:{$base[0]->codigo}", "Régimen {$base[0]->codigo}");
        if ($avisoBase !== null) {
            $avisos[] = $avisoBase;
        }

        // 1) alícuotas base por subsistema según encuadramiento
        $lineas = [];
        foreach ($this->alicuotasContribuciones as $a) {
            if ($a->encuadramiento !== $encuadramiento) {
                continue;
            }
            $aviso = $this->gate->evaluar($a->estadoValidacion, "motor_alicuotas_contribuciones:{$a->codigo}", "Alícuota {$a->codigo} ({$encuadramiento}/{$a->codigoSubsistema})");
            if ($aviso !== null) {
                $avisos[] = $aviso;
            }
            $lineas[$a->codigoSubsistema] = ['subsistema' => $a->codigoSubsistema, 'base' => $a->baseAplicable, 'porcentaje' => $a->porcentaje, 'regla' => 'BASE', 'regimen' => $base[0]->codigo, 'destino' => null];
        }
        if ($lineas === []) {
            throw MotorBloqueado::porque(CodigoBloqueo::AlicuotaAusente, "Sin alícuotas de contribución vigentes para el encuadramiento {$encuadramiento}.", 'motor_alicuotas_contribuciones');
        }

        // 2) desviaciones en orden de aplicación
        foreach ($regimenes as $r) {
            if ($r->tipoRegla === TipoRegla::Base || $r->sujetoObligado !== SujetoObligado::Empleador) {
                continue;
            }
            if ($r->duracionMeses !== null && $periodo->mesesDesde(Periodo::deFecha($fechaIngreso)) >= $r->duracionMeses) {
                $avisos[] = new Aviso('REGIMEN_VENCIDO', "Régimen {$r->codigo}: superó los {$r->duracionMeses} meses desde el ingreso; no se aplica.", "motor_regimenes_contributivos:{$r->codigo}");

                continue;
            }
            $aviso = $this->gate->evaluar($r->estadoValidacion, "motor_regimenes_contributivos:{$r->codigo}", "Régimen {$r->codigo}");
            if ($aviso !== null) {
                $avisos[] = $aviso;
            }
            $destinos = array_map('trim', explode('+', (string) $r->destino));
            switch ($r->tipoRegla) {
                case TipoRegla::Sustitutivo:
                    // reemplaza la alícuota base completa de los subsistemas destino por UNA sola línea con el % del régimen
                    foreach ($destinos as $d) {
                        unset($lineas[$d]);
                    }
                    $lineas[$r->codigo] = ['subsistema' => $destinos[0], 'base' => $r->baseAplicable, 'porcentaje' => $r->porcentaje ?? Porcentaje::cero(), 'regla' => 'SUSTITUTIVO', 'regimen' => $r->codigo, 'destino' => $r->destino];
                    break;
                case TipoRegla::Adicional:
                    $lineas[$r->codigo] = ['subsistema' => $destinos[0], 'base' => $r->baseAplicable, 'porcentaje' => $r->porcentaje ?? Porcentaje::cero(), 'regla' => 'ADICIONAL', 'regimen' => $r->codigo, 'destino' => $r->destino];
                    break;
                case TipoRegla::Destino:
                    // cambia el destinatario sin sumar: se informa como línea propia con su %, descontándolo del subsistema de origen si existe
                    $lineas[$r->codigo] = ['subsistema' => $destinos[0], 'base' => $r->baseAplicable, 'porcentaje' => $r->porcentaje ?? Porcentaje::cero(), 'regla' => 'DESTINO', 'regimen' => $r->codigo, 'destino' => $r->destino];
                    break;
                default:
                    break;
            }
        }

        return ['lineas' => array_values($lineas), 'avisos' => $avisos];
    }

    /** Suma de porcentajes efectivos (para tests: chofer con RIFL = 7 %, no 25 %). */
    public function porcentajeTotal(array $lineas): Porcentaje
    {
        $t = Porcentaje::cero();
        foreach ($lineas as $l) {
            $t = $t->mas($l['porcentaje']);
        }

        return $t;
    }
}
