<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioAlicuotas;
use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAlicuotaAporte;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAlicuotaContribucion;
use DateTimeInterface;

final class RepositorioAlicuotasEloquent implements RepositorioAlicuotas
{
    public function contribucionesVigentes(DateTimeInterface $fecha): array
    {
        $q = MotorAlicuotaContribucion::query()
            ->join('motor_subsistemas', 'motor_subsistemas.id_subsistema', '=', 'motor_alicuotas_contribuciones.id_subsistema')
            ->select('motor_alicuotas_contribuciones.*', 'motor_subsistemas.codigo as codigo_subsistema');

        return Mapeos::vigenteA($q, $fecha, 'motor_alicuotas_contribuciones.')->orderBy('motor_alicuotas_contribuciones.codigo')->get()
            ->map(fn ($f) => new AlicuotaContribucion(
                (int) $f->id_alicuota, $f->codigo, $f->encuadramiento, (int) $f->id_subsistema, $f->codigo_subsistema,
                Mapeos::porcentaje($f->porcentaje), $f->base_aplicable, Mapeos::vigencia($f), Mapeos::estado($f),
            ))->all();
    }

    public function aportesVigentes(DateTimeInterface $fecha): array
    {
        $q = MotorAlicuotaAporte::query()
            ->join('motor_subsistemas', 'motor_subsistemas.id_subsistema', '=', 'motor_alicuotas_aportes.id_subsistema')
            ->select('motor_alicuotas_aportes.*', 'motor_subsistemas.codigo as codigo_subsistema');

        return Mapeos::vigenteA($q, $fecha, 'motor_alicuotas_aportes.')->orderBy('motor_alicuotas_aportes.codigo')->get()
            ->map(fn ($f) => new AlicuotaAporte(
                (int) $f->id_alicuota_aporte, $f->codigo, (int) $f->id_subsistema, $f->codigo_subsistema, $f->codigo_condicion,
                Mapeos::porcentaje($f->porcentaje), $f->base_aplicable, (bool) $f->aplica_tope, Mapeos::vigencia($f), Mapeos::estado($f),
            ))->all();
    }
}
