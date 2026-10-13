<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\Liquidador\ContextoPeriodo;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

/**
 * Caso de control EMP-028 — Etapa 3A, agosto 2026.
 *
 * Fuente: fila anonimizada de sim/data/reconciliacion_control_2026_08.json, incluida
 * en el instructivo de Oscar (CASO_EMP028). Es un empleado de tiempo parcial
 * conciliado contra el F.931, y por eso ejercita las tres reglas que la auditoría
 * ARCA del 2026-09-25 incorporó y que no existían en el Excel del 30/08:
 *
 *   · el diferencial de obra social por jornada parcial, que suma a REM_4 y REM_8
 *     fuera de la matriz de conceptos (art. 92 ter LCT + ARCA G18);
 *   · REM_10 como base DERIVADA: REM_2 menos el importe a detraer de la Ley 27.430;
 *   · las contribuciones de seguridad social liquidadas sobre REM_10, no sobre la
 *     base nominal de cada alícuota.
 *
 * Los importes esperados son los del control, no cálculos propios.
 */
final class Etapa3aEmp028Test extends TestCase
{
    private const REM = '582313.19';          // remuneración de conceptos del período

    private const DIF_OS = '993241.10';       // diferencial de OS por tiempo parcial

    private const DETRAER = '3501.84';        // importe a detraer Ley 27.430

    private function periodo(): Periodo
    {
        return Periodo::de('2026-08');
    }

    /** Topes de agosto 2026 (ARCA): máximo 4.594.798,23 y piso previsional de REM_10. */
    private function topesDeAgosto(): array
    {
        $p = $this->periodo();
        $max = Importe::de('4594798.23');

        return [
            new Tope(1, 'TOP-AUG-MAX-REM_1', $p, 'REM_1', TipoTope::Maximo, ComponenteTope::Mensual, $max, EstadoValidacion::Confirmado),
            new Tope(2, 'TOP-AUG-MAX-REM_4', $p, 'REM_4', TipoTope::Maximo, ComponenteTope::Mensual, $max, EstadoValidacion::Confirmado),
            new Tope(3, 'TOP-AUG-MAX-REM_5', $p, 'REM_5', TipoTope::Maximo, ComponenteTope::Mensual, $max, EstadoValidacion::Confirmado),
            new Tope(4, 'TOP-AUG-MIN-REM10', $p, 'REM_10', TipoTope::Minimo, ComponenteTope::Mensual, Importe::de('141380.42'), EstadoValidacion::Confirmado),
        ];
    }

    /** Un único concepto remunerativo que aporta toda la remuneración del período. */
    private function liquidar(): \App\Motor\Dominio\Liquidador\Resultado\Liquidacion
    {
        $concepto = F::concepto('REMUN', 'Remuneración del período', TipoConcepto::Haber, Naturaleza::Remunerativo,
            EstrategiaCalculo::ImporteDirecto, ['fijo' => self::REM]);
        $conceptos = ['REMUN' => $concepto];

        $periodo = new ContextoPeriodo(
            $this->periodo(), TipoLiquidacion::Normal, Entorno::Prueba,
            F::versiones($conceptos), F::basesArca(), F::subsistemas(), F::aplicaciones(),
            F::alicuotasContribuciones(EstadoValidacion::Confirmado), F::alicuotasAportes(EstadoValidacion::Confirmado),
            $this->topesDeAgosto(), F::regimenes(EstadoValidacion::Confirmado)
        );

        $rl = F::relacionAguirre();
        $ctx = new ContextoRelacionLaboral(
            $rl, $this->periodo()->ultimoDia(), 104, 'EST_OP_INT_ANEXO', 1, '371/03',
            new \App\Motor\Dominio\Contratos\JornadaCanonica(1, 'Tiempo parcial', 24, true),
            new EncuadramientoEmpresa(1, $rl->empresaId, 'B', F::vig('2026-01-01'), EstadoValidacion::Confirmado),
            null, [], 30, '1', 'NO_INFORMADO',
            self::DETRAER,
            // El diferencial lo calcula el módulo de obra social; el motor lo recibe resuelto.
            ['REM_4' => ['OS_TIEMPO_PARCIAL' => self::DIF_OS], 'REM_8' => ['OS_TIEMPO_PARCIAL' => self::DIF_OS]]
        );

        return (new \App\Motor\Dominio\Liquidador\MotorLiquidacion)
            ->liquidar($periodo, $ctx, F::valoresParametros(), F::basicos());
    }

    public function test_el_diferencial_de_obra_social_suma_a_rem_4_y_rem_8(): void
    {
        $bases = $this->liquidar()->basesArca;

        // La matriz aporta la remuneración; el diferencial entra fuera de ella
        self::assertSame(self::REM, $bases['REM_4']->incluidos['REMUN'] ?? null);
        self::assertSame(self::DIF_OS, $bases['REM_4']->diferenciales['OS_TIEMPO_PARCIAL'] ?? null);
        // rem4 = rem8 = 1.575.554,29 en el control del F.931
        self::assertSame('1575554.29', $bases['REM_4']->importeAplicado->aTexto(2));
        self::assertSame('1575554.29', $bases['REM_8']->importeAplicado->aTexto(2));
        // las bases sin diferencial quedan en la remuneración pura
        self::assertSame(self::REM, $bases['REM_1']->importeAplicado->aTexto(2));
        self::assertSame(self::REM, $bases['REM_9']->importeAplicado->aTexto(2));
    }

    public function test_rem_10_se_deriva_de_rem_2_menos_la_detraccion(): void
    {
        $rem10 = $this->liquidar()->basesArca['REM_10'] ?? null;

        self::assertNotNull($rem10, 'REM_10 debe construirse cuando hay importe a detraer');
        self::assertSame('DERIVADA', $rem10->origenCalculo, 'no sale de la matriz de conceptos');
        self::assertSame('578811.35', $rem10->importeAplicado->aTexto(2));   // 582.313,19 − 3.501,84
    }

    public function test_las_contribuciones_de_seguridad_social_van_sobre_rem_10(): void
    {
        $liq = $this->liquidar();
        $porSubsistema = [];
        foreach ($liq->contribuciones as $c) {
            $porSubsistema[$c->codigoSubsistema] = $c;
        }

        // Importes del control F.931 para encuadramiento B
        self::assertSame('62337.98', $porSubsistema['SIPA']->importe->aTexto(2));
        self::assertSame('9203.10', $porSubsistema['INSSJP']->importe->aTexto(2));
        self::assertSame('27204.13', $porSubsistema['AAFF']->importe->aTexto(2));
        self::assertSame('5440.83', $porSubsistema['FNE']->importe->aTexto(2));

        // Y todas se liquidaron sobre REM_10, no sobre la base nominal de su alícuota
        foreach (['SIPA', 'INSSJP', 'AAFF', 'FNE'] as $s) {
            self::assertSame('REM_10', $porSubsistema[$s]->codigoBase, "{$s} debe calcularse sobre REM_10");
            self::assertSame('578811.35', $porSubsistema[$s]->base->aTexto(2));
        }
    }

    public function test_con_detraccion_cero_rem_10_se_informa_en_cero(): void
    {
        // ARCA G18: no es que no se informe, se informa en cero.
        $concepto = F::concepto('REMUN', 'Remuneración', TipoConcepto::Haber, Naturaleza::Remunerativo,
            EstrategiaCalculo::ImporteDirecto, ['fijo' => self::REM]);
        $periodo = new ContextoPeriodo($this->periodo(), TipoLiquidacion::Normal, Entorno::Prueba,
            F::versiones(['REMUN' => $concepto]), F::basesArca(), F::subsistemas(), F::aplicaciones(),
            F::alicuotasContribuciones(EstadoValidacion::Confirmado), F::alicuotasAportes(EstadoValidacion::Confirmado),
            $this->topesDeAgosto(), F::regimenes(EstadoValidacion::Confirmado));
        $rl = F::relacionAguirre();
        $ctx = new ContextoRelacionLaboral($rl, $this->periodo()->ultimoDia(), 104, 'EST_OP_INT_ANEXO', 1, '371/03',
            new \App\Motor\Dominio\Contratos\JornadaCanonica(1, 'Completa', 48, false),
            new EncuadramientoEmpresa(1, $rl->empresaId, 'B', F::vig('2026-01-01'), EstadoValidacion::Confirmado),
            null, [], 30, '1', 'NO_INFORMADO', '0');

        $rem10 = (new \App\Motor\Dominio\Liquidador\MotorLiquidacion)
            ->liquidar($periodo, $ctx, F::valoresParametros(), F::basicos())->basesArca['REM_10'] ?? null;

        self::assertNotNull($rem10);
        self::assertSame('0.00', $rem10->importeAplicado->aTexto(2));
        self::assertStringContainsString('cero', (string) $rem10->detalleDerivacion);
    }
}
