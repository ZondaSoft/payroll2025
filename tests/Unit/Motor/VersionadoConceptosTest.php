<?php

namespace Tests\Unit\Motor;

use App\Motor\Aplicacion\CasosDeUso\CompararVersion;
use App\Motor\Aplicacion\CasosDeUso\PublicarConcepto;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\MotorLiquidacion;
use App\Motor\Dominio\Servicios\ResolutorElegibilidad;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\EnMemoria\AplicabilidadEnMemoria;
use App\Motor\Infraestructura\EnMemoria\BasesZondaEnMemoria;
use App\Motor\Infraestructura\EnMemoria\ConceptosEnMemoria;
use App\Motor\Infraestructura\EnMemoria\IncidenciasArcaEnMemoria;
use App\Motor\Infraestructura\EnMemoria\RelojFijo;
use App\Motor\Infraestructura\EnMemoria\UnidadDeTrabajoEnMemoria;
use App\Motor\Infraestructura\EnMemoria\VersionesEnMemoria;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026 as F;

/** Los 7 casos obligatorios del mockup (viewCasos), sobre repositorios en memoria. */
final class VersionadoConceptosTest extends TestCase
{
    private ConceptosEnMemoria $conceptos;

    private VersionesEnMemoria $versiones;

    private PublicarConcepto $publicar;

    private CompararVersion $comparar;

    private RelojFijo $reloj;

    protected function setUp(): void
    {
        $this->conceptos = new ConceptosEnMemoria;
        $this->versiones = new VersionesEnMemoria($this->conceptos);
        $this->reloj = new RelojFijo('2026-06-15 10:00:00');
        $incid = new IncidenciasArcaEnMemoria;
        $bz = new BasesZondaEnMemoria;
        $ap = new AplicabilidadEnMemoria;
        $this->publicar = new PublicarConcepto($this->conceptos, $this->versiones, $incid, $bz, $ap, new UnidadDeTrabajoEnMemoria, $this->reloj);
        $this->comparar = new CompararVersion($this->conceptos, $this->versiones, $incid, $bz, $ap, $this->reloj);
    }

    private function conceptoFijo(string $codigo, string $importe, string $desde = '2026-01-01'): Concepto
    {
        $c = new Concepto(null, \App\Motor\Dominio\ValorObjetos\CodigoConcepto::de($codigo), "Concepto {$codigo}", TipoConcepto::Haber, Naturaleza::Remunerativo, \App\Motor\Dominio\Enums\Signo::Credito,
            \App\Motor\Dominio\Enums\ModoCalculo::ImporteFijo, Vigencia::de($desde), estrategia: EstrategiaCalculo::ImporteDirecto, fuenteImporte: FuenteComponente::NoAplica,
            importeFijo: Importe::de($importe), permiteImporteCero: true, editableManual: true);

        return $this->conceptos->guardar($c);
    }

    private function editarImporte(Concepto $c, string $importe): Concepto
    {
        $d = $c->aDefinicion();
        $d['importe_fijo'] = $importe;

        return $this->conceptos->guardar(Concepto::desdeDefinicion($d, $c->id, $c->activo, $c->estadoConfiguracion));
    }

    private function vigenteEn(int $idConcepto, string $fecha): ?\App\Motor\Dominio\Entidades\ConceptoVersion
    {
        foreach ($this->versiones->deConcepto($idConcepto) as $v) {
            if ($v->vigencia->contiene(new DateTimeImmutable($fecha))) {
                return $v;
            }
        }

        return null;
    }

    public function test_caso_1_borrador_no_modifica_el_historico(): void
    {
        $c = $this->conceptoFijo('SUE_BAS', '100');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1');
        $v1 = $this->vigenteEn($c->id, '2026-06-30');

        $this->editarImporte($c, '999'); // borrador: la fila viva cambia, la versión no

        self::assertSame('100.000000', $this->vigenteEn($c->id, '2026-06-30')->snapshot['concepto']['importe_fijo']);
        self::assertSame($v1->hash, $this->vigenteEn($c->id, '2026-06-30')->hash);
        self::assertFalse($this->comparar->borradorContraVigente($c->id)->identicos, 'el diff muestra el cambio pendiente');
    }

    public function test_caso_2_publicacion_futura(): void
    {
        $c = $this->conceptoFijo('VF', '100');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1');
        $this->editarImporte($c, '200');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-08-01'), 'v2 futura');

        self::assertSame(1, $this->vigenteEn($c->id, '2026-06-30')->numeroVersion);
        self::assertSame(2, $this->vigenteEn($c->id, '2026-08-15')->numeroVersion);
        self::assertSame('2026-07-31', $this->vigenteEn($c->id, '2026-06-30')->vigencia->hasta?->format('Y-m-d'), 'v1 se cerró el día anterior');
    }

    public function test_caso_3_version_intermedia(): void
    {
        $c = $this->conceptoFijo('VI', '1');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1');
        $this->editarImporte($c, '2');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-06-01'), 'v2');
        $this->editarImporte($c, '3');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-03-01'), 'v3 intermedia');

        $linea = array_map(fn ($v) => "v{$v->numeroVersion}: {$v->vigencia->desde->format('Y-m-d')}→".($v->vigencia->hasta?->format('Y-m-d') ?? '—'), $this->versiones->deConcepto($c->id));
        self::assertSame(['v1: 2026-01-01→2026-02-28', 'v3: 2026-03-01→2026-05-31', 'v2: 2026-06-01→—'], $linea);
    }

    public function test_caso_4_baja_futura(): void
    {
        $c = $this->conceptoFijo('VB', '10');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1');
        // baja desde 01/01/2027: se reemplaza la versión vigente (no usada) con vigencia_hasta
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'baja 01/01/2027', vigenciaHasta: new DateTimeImmutable('2026-12-31'));

        self::assertNotNull($this->vigenteEn($c->id, '2026-12-15'), 'diciembre 2026 disponible');
        self::assertNull($this->vigenteEn($c->id, '2027-01-15'), 'enero 2027 no disponible');
        self::assertCount(1, $this->versiones->deConcepto($c->id));
    }

    public function test_version_usada_en_liquidacion_no_se_reemplaza(): void
    {
        $c = $this->conceptoFijo('VU', '10');
        $v = $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1')['version'];
        $this->versiones->marcarUsadas([$v->id]);
        $this->editarImporte($c, '20');

        $this->expectException(MotorBloqueado::class);
        $this->expectExceptionMessage('ya fue utilizada en una liquidación cerrada');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'intento de pisar');
    }

    public function test_publicar_sin_cambios_no_crea_version(): void
    {
        $c = $this->conceptoFijo('SC', '10');
        $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-01-01'), 'v1');
        $r = $this->publicar->ejecutar($c->id, new DateTimeImmutable('2026-05-01'), 'repetida');

        self::assertTrue($r['sin_cambios']);
        self::assertCount(1, $this->versiones->deConcepto($c->id));
    }

    public function test_caso_5_indant_exclusivo_de_liquidacion_final(): void
    {
        $indant = F::conceptos()['INDANT'];
        $e = new ResolutorElegibilidad;

        self::assertFalse($e->evaluar($indant, TipoLiquidacion::Normal, F::periodo())['elegible']);
        self::assertTrue($e->evaluar($indant, TipoLiquidacion::Final, F::periodo())['elegible']);
    }

    public function test_caso_6_cuota_sindical_solo_afiliado_vigente(): void
    {
        // CUOSIN es un descuento MODULO_SISTEMA sin base ARCA: el motor NO lo calcula (lo resuelve el módulo sindical)
        // y lo deja explícitamente como no ejecutado con motivo, nunca como cero silencioso.
        $motor = new MotorLiquidacion;
        $liq = $motor->liquidar(F::contextoPeriodo(Entorno::Prueba), F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), []), F::valoresParametros(), F::basicos());
        $ne = array_column($liq->noEjecutados, 'motivo', 'codigo');

        self::assertArrayHasKey('CUOSIN', $ne);
        self::assertStringContainsString('módulo externo', $ne['CUOSIN']);
        self::assertNotContains('CUOSIN', array_map(fn ($a) => $a->codigoConcepto, $liq->aportes));
    }

    public function test_caso_7_fsr_no_se_re_descuenta(): void
    {
        $motor = new MotorLiquidacion;
        $liq = $motor->liquidar(F::contextoPeriodo(Entorno::Prueba), F::contexto(F::relacionAguirre(), 1, F::modeloAntiguedadEscala(), []), F::valoresParametros(), F::basicos());

        $aportes = array_map(fn ($a) => $a->codigoConcepto, $liq->aportes);
        self::assertSame(['APJUB', 'APINSSJYP'], array_values(array_filter($aportes, fn ($x) => in_array($x, ['APJUB', 'APINSSJYP'], true))), 'solo SIPA e INSSJP se descuentan por alícuota normativa');
        self::assertNotContains('FSR_APORTE', $aportes);
        self::assertNotContains('APFSR', $aportes);
        // OS (3 %) y FSR viven en el módulo de obra social: APOSOC queda como no ejecutado con motivo, no como descuento duplicado
        $ne = array_column($liq->noEjecutados, 'motivo', 'codigo');
        self::assertArrayHasKey('APOSOC', $ne);
        self::assertStringContainsString('obra social', $ne['APOSOC']);
    }
}
