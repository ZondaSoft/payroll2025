<?php

namespace Tests\Feature\Motor;

use App\Motor\Aplicacion\Puertos\RepositorioFormulas;
use App\Motor\Aplicacion\Servicios\FabricaCatalogoDsl;
use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Formulas\GrafoCalculo;
use App\Motor\Dominio\Formulas\ValidadorVersion;
use App\Motor\Dominio\Formulas\VersionFormula;
use Database\Seeders\MotorFormulasV5Seeder;
use Database\Seeders\MotorVersionesInicialesSeeder;
use DateTimeImmutable;
use Tests\Support\Motor\EscenarioLibro;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/**
 * Todas las fórmulas publicadas, pasadas por la puerta de publicación contra el
 * catálogo real de la base.
 *
 * Es el test que encuentra los huecos de configuración antes de que los encuentre
 * una liquidación: una referencia a un parámetro que nadie creó, una novedad sin
 * declarar, una unidad que el catálogo no dice. Si una fórmula publicada no
 * valida, está publicada de más.
 */
final class ValidacionFormulasPublicadasTest extends TestCase
{
    use UsaEsquemaMotor;

    private const FECHA = '2026-07-31';

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
        $this->seed(MotorFormulasV5Seeder::class);
        // La configuración por categoría (VALCAT) no la trae la migración normativa:
        // depende de las categorías de cada cliente. Sin ella, PLUSPLUR y TOLHOR
        // están publicados referenciando algo que no existe — lo verifica el último test.
        EscenarioLibro::sembrar();
    }

    private function catalogo(): CatalogoDsl
    {
        return app(FabricaCatalogoDsl::class)->construir(new DateTimeImmutable(self::FECHA));
    }

    /** @return list<VersionFormula> */
    private function versiones(): array
    {
        return app(RepositorioFormulas::class)->vigentesA(new DateTimeImmutable(self::FECHA));
    }

    public function test_hay_formulas_publicadas_para_validar(): void
    {
        $versiones = $this->versiones();

        self::assertGreaterThanOrEqual(20, count($versiones));
        // BASICO publica dos variantes con la misma vigencia: el repositorio las trae
        // a las dos y es el perfil el que elige.
        $basico = array_values(array_filter($versiones, fn (VersionFormula $v) => $v->codigoConcepto === 'BASICO'));
        self::assertSame(['JORNADA_PACTADA', 'LARGA_DISTANCIA'], array_map(fn (VersionFormula $v) => $v->variante, $basico));
    }

    public function test_todas_las_formulas_publicadas_validan_contra_el_catalogo(): void
    {
        $validador = new ValidadorVersion;
        $catalogo = $this->catalogo();
        $fallan = [];

        foreach ($this->versiones() as $v) {
            $r = $validador->validar($v, $catalogo);
            if (! $r->publica()) {
                $fallan[$v->codigoConcepto.'/'.$v->variante] = array_map(
                    fn (array $e) => $e['codigo'].': '.$e['mensaje'], $r->errores
                );
            }
        }

        self::assertSame([], $fallan, "fórmulas publicadas que no validan:\n".json_encode($fallan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function test_el_importe_de_toda_formula_publicada_devuelve_pesos(): void
    {
        $validador = new ValidadorVersion;
        $catalogo = $this->catalogo();

        foreach ($this->versiones() as $v) {
            if ($v->esModulo()) {
                continue;
            }
            $r = $validador->validar($v, $catalogo);
            self::assertSame('ARS', (string) $r->unidadImporte,
                "{$v->codigoConcepto}/{$v->variante} devuelve {$r->unidadImporte}");
        }
    }

    public function test_el_grafo_del_catalogo_completo_no_tiene_ciclos(): void
    {
        $g = (new GrafoCalculo)->construir($this->versiones(), $this->catalogo());

        self::assertFalse($g['ciclo'], 'nodos en ciclo: '.implode(', ', $g['nodosEnCiclo']));
        // Las cinco bases de Zonda y los conceptos del libro, todos ordenados.
        self::assertGreaterThanOrEqual(25, count($g['orden']));
    }

    public function test_ninguna_base_se_consume_a_si_misma(): void
    {
        $catalogo = $this->catalogo();
        $validador = new ValidadorVersion;

        foreach ($this->versiones() as $v) {
            $codigos = $validador->validar($v, $catalogo)->codigos();
            self::assertNotContains('DSL_CONSUME_E_INTEGRA', $codigos,
                "{$v->codigoConcepto} consume una base que integra");
            self::assertNotContains('DSL_AUTORREFERENCIA', $codigos, "{$v->codigoConcepto} se referencia a sí mismo");
        }
    }

    public function test_las_novedades_del_catalogo_declaran_su_regla_de_agregacion(): void
    {
        // Sin regla_agregacion el DSL no puede publicar una fórmula que use NOV():
        // «cuánto vale la novedad» sería ambiguo cuando hay dos del mismo tipo.
        foreach ($this->catalogo()->novedades as $codigo => $n) {
            self::assertContains($n['regla_agregacion'] ?? null, ['SUMA', 'UNICO'], "la novedad {$codigo}");
        }
    }

    public function test_sin_la_declaracion_por_categoria_las_formulas_de_valcat_no_publican(): void
    {
        \Illuminate\Support\Facades\DB::table('motor_categoria_concepto_vigencia')->delete();
        \Illuminate\Support\Facades\DB::table('motor_categoria_concepto')->delete();

        $validador = new ValidadorVersion;
        $catalogo = $this->catalogo();
        $sinDeclarar = [];
        foreach ($this->versiones() as $v) {
            if (in_array('DSL_REFERENCIA_INEXISTENTE', $validador->validar($v, $catalogo)->codigos(), true)) {
                $sinDeclarar[] = $v->codigoConcepto;
            }
        }

        // PLUSPLUR y TOLHOR son las dos fórmulas que leen VALCAT. Que se publicaran
        // sin la configuración por categoría es un hueco del origen, no del motor:
        // con la tabla vacía la referencia no existe y la versión no debería publicar.
        self::assertSame(['PLUSPLUR', 'TOLHOR'], $sinDeclarar);
    }

    public function test_las_cinco_bases_de_zonda_tienen_composicion(): void
    {
        $bases = $this->catalogo()->bases;

        self::assertSame([
            'BASE_ACUERDO_5_6_2', 'BASE_ANTIGUEDAD', 'BASE_HORA_NORMAL', 'BASE_NO_REMUNERATIVA', 'BASE_SINDICAL',
        ], array_keys($bases));
        foreach ($bases as $codigo => $base) {
            self::assertNotEmpty($base['integrantes'], "la base {$codigo} no tiene integrantes vigentes");
        }
    }
}
