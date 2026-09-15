<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Servicios\GateValidacion;
use App\Motor\Dominio\Servicios\ResolutorRegimenes;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class ResolutorRegimenesTest extends TestCase
{
    private function resolutor(Entorno $e = Entorno::Prueba): ResolutorRegimenes
    {
        return new ResolutorRegimenes(F::alicuotasContribuciones(EstadoValidacion::Confirmado), F::regimenes(EstadoValidacion::Confirmado), new GateValidacion($e));
    }

    public function test_encuadramiento_a_suma_20_40_y_b_18(): void
    {
        $r = $this->resolutor();
        $a = $r->contribuciones('A', ['GENERAL'], F::periodo(), new DateTimeImmutable('2018-06-11'));
        $b = $r->contribuciones('B', ['GENERAL'], F::periodo(), new DateTimeImmutable('2018-06-11'));

        self::assertSame('20.4000', $r->porcentajeTotal($a['lineas'])->porciento());
        self::assertSame('18.0000', $r->porcentajeTotal($b['lineas'])->porciento());
        self::assertCount(4, $b['lineas']);
        self::assertSame(['BASE'], array_unique(array_column($b['lineas'], 'regla')));
    }

    public function test_chofer_con_transp_cargas_suma_2_adicional(): void
    {
        $r = $this->resolutor();
        $x = $r->contribuciones('B', ['GENERAL', 'TRANSP_CARGAS'], F::periodo(), new DateTimeImmutable('2009-07-01'));

        self::assertSame('20.0000', $r->porcentajeTotal($x['lineas'])->porciento(), '18 % + 2 % adicional');
        $adic = array_values(array_filter($x['lineas'], fn ($l) => $l['regla'] === 'ADICIONAL'))[0];
        self::assertSame('SIPA', $adic['subsistema']);
        self::assertSame('REM_2', $adic['base']);
    }

    public function test_rifl_sustituye_sipa_inssjp_aaff_fne_por_5_y_no_suma_25(): void
    {
        $r = $this->resolutor();
        $x = $r->contribuciones('B', ['GENERAL', 'RIFL', 'TRANSP_CARGAS'], F::periodo(), new DateTimeImmutable('2026-05-01'));

        self::assertSame('7.0000', $r->porcentajeTotal($x['lineas'])->porciento(), 'RIFL 5 % + adicional 2 % = 7 %, nunca 25 %');
        $reglas = array_column($x['lineas'], 'regla');
        self::assertContains('SUSTITUTIVO', $reglas);
        self::assertNotContains('BASE', $reglas, 'la alícuota base fue reemplazada completa');
    }

    public function test_rifl_vencido_a_los_48_meses_no_se_aplica(): void
    {
        $r = $this->resolutor();
        $x = $r->contribuciones('B', ['GENERAL', 'RIFL'], F::periodo(), new DateTimeImmutable('2022-05-01'));

        self::assertSame('18.0000', $r->porcentajeTotal($x['lineas'])->porciento());
        self::assertSame('REGIMEN_VENCIDO', $x['avisos'][0]->codigo);
    }

    public function test_sin_regimen_base_bloquea(): void
    {
        try {
            $this->resolutor()->contribuciones('B', ['TRANSP_CARGAS'], F::periodo(), new DateTimeImmutable('2009-07-01'));
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::RegimenSinBase, $e->codigo);
        }
    }

    public function test_en_produccion_sin_confirmar_bloquea(): void
    {
        $r = new ResolutorRegimenes(F::alicuotasContribuciones(), F::regimenes(), new GateValidacion(Entorno::Produccion));
        try {
            $r->contribuciones('B', ['GENERAL'], F::periodo(), new DateTimeImmutable('2018-06-11'));
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::EstadoNoHabilitado, $e->codigo);
        }
    }
}
