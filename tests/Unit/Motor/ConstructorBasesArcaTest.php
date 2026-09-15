<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\LineaHaber;
use App\Motor\Dominio\Servicios\ConstructorBasesArca;
use App\Motor\Dominio\ValorObjetos\Importe;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class ConstructorBasesArcaTest extends TestCase
{
    private function aplicaciones(): array
    {
        $out = [];
        foreach (F::aplicaciones() as $a) {
            $out[$a->clave()] = $a;
        }

        return $out;
    }

    public function test_remunerativos_integran_y_no_remunerativos_se_excluyen(): void
    {
        $c = F::conceptos();
        $haberes = [new LineaHaber($c['BASICO'], Importe::de('1000000'), Importe::de('1000000')), new LineaHaber($c['VIATKM'], Importe::de('50000'), Importe::de('50000'))];
        $r = (new ConstructorBasesArca)->construir($this->aplicaciones(), F::incidenciasArca($c), $haberes, F::periodo()->ultimoDia(), Entorno::Produccion);

        self::assertSame('1000000.00', $r['bases']['REM_1']->importeAplicado->aTexto(2));
        self::assertSame('1000000.00', $r['bases']['REM_9']->importeAplicado->aTexto(2));
        self::assertSame(['VIATKM' => 'incidencia N'], $r['bases']['REM_1']->excluidos);
        self::assertArrayNotHasKey('REM_6', $r['bases'], 'sin aplicación oficial no se construye');
        self::assertSame([], $r['avisos']);
    }

    public function test_pendiente_bloquea_en_produccion_y_avisa_en_prueba(): void
    {
        $c = F::conceptos();
        $haberes = [new LineaHaber($c['BASICO'], Importe::de('1000000'), Importe::de('1000000'))];
        $incid = F::incidenciasArca($c, pendientes: ['BASICO']);

        try {
            (new ConstructorBasesArca)->construir($this->aplicaciones(), $incid, $haberes, F::periodo()->ultimoDia(), Entorno::Produccion);
            self::fail();
        } catch (MotorBloqueado $e) {
            self::assertSame(CodigoBloqueo::IncidenciaPendiente, $e->codigo);
        }

        $r = (new ConstructorBasesArca)->construir($this->aplicaciones(), $incid, $haberes, F::periodo()->ultimoDia(), Entorno::Prueba);
        self::assertSame('0.00', $r['bases']['REM_1']->importeAplicado->aTexto(2));
        self::assertSame('INCIDENCIA_PENDIENTE', $r['avisos'][0]->codigo);
    }

    public function test_ausencia_de_fila_no_equivale_a_n(): void
    {
        $c = F::conceptos();
        $haberes = [new LineaHaber($c['BASICO'], Importe::de('100'), Importe::de('100'))];
        $r = (new ConstructorBasesArca)->construir($this->aplicaciones(), [], $haberes, F::periodo()->ultimoDia(), Entorno::Prueba);
        self::assertStringContainsString('PENDIENTE', $r['bases']['REM_1']->excluidos['BASICO']);
    }
}
