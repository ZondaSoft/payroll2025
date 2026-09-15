<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Servicios\ValidadorConcepto;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

final class ValidadorConceptoTest extends TestCase
{
    private function codigos(array $r): array
    {
        return array_column($r, 'codigo');
    }

    public function test_codigo_duplicado_y_activo_sin_estrategia(): void
    {
        $v = new ValidadorConcepto;
        $c = F::concepto('BASICO', 'x', TipoConcepto::Haber, Naturaleza::Remunerativo, null);
        $r = $v->validar($c, codigosExistentes: ['BASICO']);

        self::assertContains('V01', $this->codigos($r));
        self::assertContains('V02', $this->codigos($r));
        self::assertTrue($v->tieneErrores($r));
    }

    public function test_r5_rango_arca_por_naturaleza(): void
    {
        $v = new ValidadorConcepto;
        $c = F::concepto('X', 'x', TipoConcepto::Haber, Naturaleza::Remunerativo, EstrategiaCalculo::ImporteDirecto, ['fi' => FuenteComponente::Manual]);
        $d = $c->aDefinicion();
        $d['codigo_afip'] = 520000; // rango de NO_REMUNERATIVO
        $mal = \App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, 1);
        self::assertContains('V11', $this->codigos($v->validar($mal)));

        $d['codigo_afip'] = 110000;
        self::assertNotContains('V11', $this->codigos($v->validar(\App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, 1))));
    }

    public function test_r13_circularidad_directa_e_indirecta(): void
    {
        $v = new ValidadorConcepto;
        $he = F::conceptos()['HE50']; // consume BASE_HORA_NORMAL
        $integraLaMisma = [new IncidenciaBaseZonda(null, 1, 'BASE_HORA_NORMAL', 'HE50', true, F::vig(), EstadoValidacion::SinConfirmar)];
        self::assertContains('V06', $this->codigos($v->validar($he, basesZonda: $integraLaMisma)));

        // indirecta: HE50 consume BASE_HORA_NORMAL e integra BASE_X; OTRO consume BASE_X e integra BASE_HORA_NORMAL
        $integraOtra = [new IncidenciaBaseZonda(null, 2, 'BASE_X', 'HE50', true, F::vig(), EstadoValidacion::SinConfirmar)];
        $r = $v->validar($he, basesZonda: $integraOtra, composicionBases: ['BASE_HORA_NORMAL' => ['OTRO']], consumos: ['OTRO' => 'BASE_X']);
        self::assertContains('V07', $this->codigos($r));
    }

    public function test_r10_remunerativo_debe_integrar_todas_las_bases(): void
    {
        $v = new ValidadorConcepto;
        $c = F::conceptos()['BASICO'];
        $incid = F::incidenciasArca(['BASICO' => $c])['BASICO'];
        $parcial = array_slice($incid, 0, 3);
        $r = $v->validar($c, incidenciasArca: $parcial);
        $r10 = array_values(array_filter($r, fn ($x) => $x['codigo'] === 'R10'));
        self::assertCount(1, $r10);
        self::assertSame('AVISO', $r10[0]['nivel']);
        self::assertNotContains('R10', $this->codigos($v->validar($c, incidenciasArca: $incid)));
    }

    public function test_r7_periodicidad_y_avisos_de_comportamiento(): void
    {
        $v = new ValidadorConcepto;
        $c = F::conceptos()['BASICO'];
        $d = $c->aDefinicion();
        $d['tipo_repeticion'] = 'CADA_N_MESES';
        $d['intervalo_meses'] = 0;
        self::assertContains('R07', $this->codigos($v->validar(\App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, 1))));

        $d = $c->aDefinicion();
        $d['tipo_repeticion'] = 'TODOS_LOS_MESES';
        $d['cantidad_meses'] = 3;
        self::assertContains('R07', $this->codigos($v->validar(\App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, 1))));

        $d = $c->aDefinicion();
        $d['signo'] = 'DEBITO';
        $d['requiere_novedad'] = true;
        $d['permite_importe_cero'] = true;
        $r = $v->validar(\App\Motor\Dominio\Entidades\Concepto::desdeDefinicion($d, 1));
        self::assertContains('SIGNO', $this->codigos($r));
        self::assertContains('CERO', $this->codigos($r));
        self::assertFalse($v->tieneErrores($r), 'son avisos, no errores');
    }
}
