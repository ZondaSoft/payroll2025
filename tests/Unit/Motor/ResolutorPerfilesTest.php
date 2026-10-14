<?php

namespace Tests\Unit\Motor;

use App\Motor\Dominio\Entidades\Perfil;
use App\Motor\Dominio\Entidades\PerfilConcepto;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Servicios\ResolutorPerfiles;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Motor\CasoJulio2026;

/**
 * La precedencia de perfiles del contrato, con los dos legajos del libro.
 *
 * Lo que decide el perfil no es sólo si el concepto aparece: también con qué
 * variante se liquida. El BASICO del 7019 se prorratea por jornada pactada y el
 * del 221 es el de escala, y la diferencia la pone el perfil, no la fórmula.
 */
final class ResolutorPerfilesTest extends TestCase
{
    private function perfil(
        string $codigo, TipoAmbito $nivel, array $conceptos, ?int $convenio = null,
        ?int $categoria = null, ?int $empresa = null, ?int $rl = null, bool $activo = true,
        ?string $desde = '2026-01-01', ?string $hasta = null,
    ): Perfil {
        $reglas = [];
        foreach ($conceptos as $cod => [$aplica, $variante, $motivo]) {
            $reglas[] = new PerfilConcepto(null, $cod, $aplica, Vigencia::de('2026-01-01'), $variante, $motivo);
        }

        return new Perfil(null, $codigo, $codigo, $nivel, Vigencia::de($desde, $hasta),
            $rl, $empresa, $categoria, $convenio, $activo, $reglas);
    }

    /** El perfil de convenio del 371/03, como está en motor_perfil del v5. */
    private function cct371(): Perfil
    {
        return $this->perfil('CCT_371_03', TipoAmbito::Convenio, [
            'BASICO' => [true, 'JORNADA_PACTADA', 'Jornada pactada individual (DSL §22).'],
            'ANTIG' => [true, 'UNICA', null],
            'ASIST' => [true, 'UNICA', 'Concepto del CCT 371/03.'],
            'HE50' => [true, 'UNICA', null],
            'MANFON' => [true, 'UNICA', null],
        ], convenio: 1);
    }

    private function cct4089(): Perfil
    {
        return $this->perfil('CCT_40_89', TipoAmbito::Convenio, [
            'BASICO' => [true, 'LARGA_DISTANCIA', null],
            'ANTIG' => [true, 'UNICA', null],
            'HE50' => [false, null, 'En larga distancia las horas extras no corresponden (H-4).'],
        ], convenio: 2);
    }

    private function ctx7019(): ContextoRelacionLaboral
    {
        return CasoJulio2026::contexto(CasoJulio2026::relacionAguirre(), 1, null, []);
    }

    private function ctx221(): ContextoRelacionLaboral
    {
        return CasoJulio2026::contexto(CasoJulio2026::relacionAlfaro(), 2, null, []);
    }

    public function test_el_perfil_de_convenio_alcanza_al_trabajador_de_ese_convenio(): void
    {
        $d = (new ResolutorPerfiles)->decidir([$this->cct371(), $this->cct4089()], ['BASICO'], $this->ctx7019());

        self::assertTrue($d['BASICO']->aplica);
        self::assertSame('JORNADA_PACTADA', $d['BASICO']->variante);
        self::assertSame('CONVENIO', $d['BASICO']->nivel);
        self::assertSame('CCT_371_03', $d['BASICO']->codigoPerfil);
    }

    public function test_el_mismo_concepto_cambia_de_variante_segun_el_convenio(): void
    {
        $perfiles = [$this->cct371(), $this->cct4089()];
        $r = new ResolutorPerfiles;

        self::assertSame('JORNADA_PACTADA', $r->decidir($perfiles, ['BASICO'], $this->ctx7019())['BASICO']->variante);
        self::assertSame('LARGA_DISTANCIA', $r->decidir($perfiles, ['BASICO'], $this->ctx221())['BASICO']->variante);
    }

    public function test_el_perfil_del_trabajador_le_gana_al_del_convenio(): void
    {
        $legajo = $this->perfil('LEGAJO_7019', TipoAmbito::Trabajador, [
            'MANFON' => [false, null, 'El legajo no tiene manejo de fondos.'],
        ], rl: 3);

        $d = (new ResolutorPerfiles)->decidir([$this->cct371(), $legajo], ['MANFON', 'ASIST'], $this->ctx7019());

        self::assertFalse($d['MANFON']->aplica);
        self::assertSame('TRABAJADOR', $d['MANFON']->nivel);
        self::assertSame('El legajo no tiene manejo de fondos.', $d['MANFON']->motivo);
        // El nivel TRABAJADOR no se pronuncia sobre ASIST: decide el convenio.
        self::assertTrue($d['ASIST']->aplica);
        self::assertSame('CONVENIO', $d['ASIST']->nivel);
    }

    public function test_la_precedencia_completa_del_contrato(): void
    {
        $perfiles = [
            $this->perfil('GEN', TipoAmbito::General, ['ADTIT' => [true, 'GENERAL', null]]),
            $this->perfil('CONV', TipoAmbito::Convenio, ['ADTIT' => [true, 'CONVENIO', null]], convenio: 1),
            $this->perfil('CAT', TipoAmbito::Categoria, ['ADTIT' => [true, 'CATEGORIA', null]], categoria: 104),
            $this->perfil('EMP', TipoAmbito::Empresa, ['ADTIT' => [true, 'EMPRESA', null]], empresa: 1),
            $this->perfil('TRAB', TipoAmbito::Trabajador, ['ADTIT' => [true, 'TRABAJADOR', null]], rl: 3),
        ];
        $r = new ResolutorPerfiles;
        $ctx = $this->ctx7019();

        self::assertSame('TRABAJADOR', $r->decidir($perfiles, ['ADTIT'], $ctx)['ADTIT']->variante);
        array_pop($perfiles);
        self::assertSame('EMPRESA', $r->decidir($perfiles, ['ADTIT'], $ctx)['ADTIT']->variante);
        array_pop($perfiles);
        self::assertSame('CATEGORIA', $r->decidir($perfiles, ['ADTIT'], $ctx)['ADTIT']->variante);
        array_pop($perfiles);
        self::assertSame('CONVENIO', $r->decidir($perfiles, ['ADTIT'], $ctx)['ADTIT']->variante);
        array_pop($perfiles);
        self::assertSame('GENERAL', $r->decidir($perfiles, ['ADTIT'], $ctx)['ADTIT']->variante);
    }

    public function test_dos_perfiles_del_mismo_nivel_que_se_contradicen_bloquean(): void
    {
        $perfiles = [
            $this->perfil('CONV_A', TipoAmbito::Convenio, ['ASIST' => [true, 'UNICA', null]], convenio: 1),
            $this->perfil('CONV_B', TipoAmbito::Convenio, ['ASIST' => [false, null, 'no corresponde']], convenio: 1),
        ];

        $d = (new ResolutorPerfiles)->decidir($perfiles, ['ASIST'], $this->ctx7019());

        self::assertTrue($d['ASIST']->estaBloqueada());
        self::assertSame(ResolutorPerfiles::AMBIGUO, $d['ASIST']->codigoBloqueo);
        self::assertStringContainsString('CONV_A, CONV_B', $d['ASIST']->motivo);
    }

    public function test_dos_perfiles_del_mismo_nivel_que_dicen_lo_mismo_no_bloquean(): void
    {
        $perfiles = [
            $this->perfil('CONV_A', TipoAmbito::Convenio, ['ASIST' => [true, 'UNICA', null]], convenio: 1),
            $this->perfil('CONV_B', TipoAmbito::Convenio, ['ASIST' => [true, 'UNICA', null]], convenio: 1),
        ];

        $d = (new ResolutorPerfiles)->decidir($perfiles, ['ASIST'], $this->ctx7019());

        self::assertFalse($d['ASIST']->estaBloqueada());
        self::assertSame('UNICA', $d['ASIST']->variante);
    }

    public function test_que_ningun_perfil_se_pronuncie_bloquea_en_vez_de_omitir(): void
    {
        $d = (new ResolutorPerfiles)->decidir([$this->cct371()], ['INAVAC'], $this->ctx7019());

        self::assertTrue($d['INAVAC']->estaBloqueada());
        self::assertSame(ResolutorPerfiles::SIN_REGLA, $d['INAVAC']->codigoBloqueo);
    }

    public function test_incluir_un_concepto_sin_variante_es_error_de_configuracion(): void
    {
        $perfiles = [$this->perfil('CONV', TipoAmbito::Convenio, ['BASICO' => [true, null, null]], convenio: 1)];

        $d = (new ResolutorPerfiles)->decidir($perfiles, ['BASICO'], $this->ctx7019());

        self::assertSame(ResolutorPerfiles::SIN_VARIANTE, $d['BASICO']->codigoBloqueo);
        self::assertStringContainsString('sin decir con qué variante', $d['BASICO']->motivo);
    }

    public function test_un_perfil_de_otro_convenio_no_alcanza_al_trabajador(): void
    {
        $d = (new ResolutorPerfiles)->decidir([$this->cct4089()], ['BASICO'], $this->ctx7019());

        self::assertSame(ResolutorPerfiles::SIN_REGLA, $d['BASICO']->codigoBloqueo);
    }

    public function test_un_perfil_inactivo_o_fuera_de_vigencia_no_cuenta(): void
    {
        $r = new ResolutorPerfiles;
        $ctx = $this->ctx7019();

        $inactivo = $this->perfil('CONV', TipoAmbito::Convenio, ['BASICO' => [true, 'JORNADA_PACTADA', null]], convenio: 1, activo: false);
        self::assertSame(ResolutorPerfiles::SIN_REGLA, $r->decidir([$inactivo], ['BASICO'], $ctx)['BASICO']->codigoBloqueo);

        $vencido = $this->perfil('CONV', TipoAmbito::Convenio, ['BASICO' => [true, 'JORNADA_PACTADA', null]],
            convenio: 1, desde: '2025-01-01', hasta: '2025-12-31');
        self::assertSame(ResolutorPerfiles::SIN_REGLA, $r->decidir([$vencido], ['BASICO'], $ctx)['BASICO']->codigoBloqueo);
    }

    public function test_la_exclusion_estructural_del_40_89_viaja_con_su_motivo(): void
    {
        $d = (new ResolutorPerfiles)->decidir([$this->cct4089()], ['HE50'], $this->ctx221());

        self::assertFalse($d['HE50']->aplica);
        self::assertFalse($d['HE50']->estaBloqueada());
        self::assertStringContainsString('H-4', $d['HE50']->motivo);
    }

    public function test_los_perfiles_aplicables_salen_ordenados_por_precedencia(): void
    {
        $perfiles = [
            $this->perfil('GEN', TipoAmbito::General, []),
            $this->perfil('TRAB', TipoAmbito::Trabajador, [], rl: 3),
            $this->perfil('CONV', TipoAmbito::Convenio, [], convenio: 1),
            $this->perfil('OTRO_CONV', TipoAmbito::Convenio, [], convenio: 2),
        ];

        $orden = array_map(fn (Perfil $p) => $p->codigo, (new ResolutorPerfiles)->aplicables($perfiles, $this->ctx7019()));

        self::assertSame(['TRAB', 'CONV', 'GEN'], $orden);
    }
}
