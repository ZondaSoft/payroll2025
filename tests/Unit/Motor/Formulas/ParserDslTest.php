<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\ErrorDsl;
use App\Motor\Dominio\Formulas\Nodo;
use App\Motor\Dominio\Formulas\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Sintaxis del DSL v1.1 — port de los bloques A y B de src/test_dsl_v11.js. */
final class ParserDslTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser;
    }

    #[DataProvider('expresionesValidas')]
    public function test_parsea_las_formulas_del_contrato(string $expresion, string $raiz): void
    {
        self::assertSame($raiz, $this->parser->parsear($expresion)->k);
    }

    public static function expresionesValidas(): array
    {
        return [
            'básico por jornada pactada' => ['VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)', Nodo::ARITMETICA],
            'básico de larga distancia' => ['VAR(BASCAT)', Nodo::REFERENCIA],
            'antigüedad por módulo' => ['BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)', Nodo::ARITMETICA],
            'hora normal' => ['BASE(BASE_HORA_NORMAL) / VAR(HMJORN)', Nodo::ARITMETICA],
            'horas extras con recargo' => ['CANT * VALUNI * 1.5', Nodo::ARITMETICA],
            'feriado por día' => ['BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)', Nodo::ARITMETICA],
            'porcentaje sobre concepto' => ['CONC(BASICO) * PARAM(PORC_ASISTENCIA)', Nodo::ARITMETICA],
            'valor categórico' => ['CONC(BASICO) * VALCAT(PLUSPLUR)', Nodo::ARITMETICA],
            'pernoctada con adicional' => ['PARAM(VALOR_PERNOCTADA_BASE) * (1 + PARAM(PORC_ADIC_NO_REMUNERATIVO))', Nodo::ARITMETICA],
            'regla de asistencia completa' => [
                'SI( NOV(INASIS) > PARAM(INASIS_MAX) O NOV(LLEGTAR) > PARAM(LLEGTAR_MAX), 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA) )',
                Nodo::FUNCION,
            ],
            'negación' => ['-VAR(BASCAT)', Nodo::NEGACION],
            'comparación simple' => ['NOV(INASIS) > PARAM(INASIS_MAX)', Nodo::COMPARACION],
            'MIN de dos magnitudes' => ['MIN(CONC(BASICO), BASE(BASE_HORA_NORMAL))', Nodo::FUNCION],
            'redondear' => ['REDONDEAR(CONC(BASICO), 2)', Nodo::FUNCION],
        ];
    }

    #[DataProvider('expresionesRechazadas')]
    public function test_rechaza_lo_que_no_pertenece_al_dsl(string $expresion, string $codigo): void
    {
        try {
            $this->parser->parsear($expresion);
            self::fail("Se esperaba {$codigo} para: {$expresion}");
        } catch (ErrorDsl $e) {
            self::assertSame($codigo, $e->codigo, $e->getMessage());
        }
    }

    public static function expresionesRechazadas(): array
    {
        return [
            'AND no es del lenguaje' => ['CONC(BASICO) > 0 AND CONC(ANTIG) > 0', 'DSL_TOKEN_DESCONOCIDO'],
            'OR tampoco' => ['CONC(BASICO) > 0 OR CONC(ANTIG) > 0', 'DSL_TOKEN_DESCONOCIDO'],
            'NOT tampoco' => ['NOT CONC(BASICO)', 'DSL_TOKEN_DESCONOCIDO'],
            'el operador <> se escribe !=' => ['CONC(BASICO) <> 0', 'DSL_TOKEN_DESCONOCIDO'],
            'sin literales de texto' => ["CONC(BASICO) = 'X'", 'DSL_TOKEN_DESCONOCIDO'],
            'función inexistente' => ['PROMEDIO(CONC(BASICO), 6)', 'DSL_TOKEN_DESCONOCIDO'],
            'SI con dos argumentos' => ['SI(CONC(BASICO) > 0, 1)', 'DSL_ARGUMENTOS_INVALIDOS'],
            'ABS con dos argumentos' => ['ABS(CONC(BASICO), 2)', 'DSL_ARGUMENTOS_INVALIDOS'],
            'referencia sin forma canónica' => ['BASICO * 2', 'DSL_REFERENCIA_NO_CANONICA'],
            'número mal formado' => ['CONC(BASICO) * 1.2.3', 'DSL_SINTAXIS_INVALIDA'],
            'expresión incompleta' => ['CONC(BASICO) *', 'DSL_SINTAXIS_INVALIDA'],
            'paréntesis sin cerrar' => ['(CONC(BASICO) * 2', 'DSL_SINTAXIS_INVALIDA'],
            'sobra contenido' => ['CONC(BASICO) 2', 'DSL_SINTAXIS_INVALIDA'],
            'carácter desconocido' => ['CONC(BASICO) % 2', 'DSL_TOKEN_DESCONOCIDO'],
            // un número SÍ es un código válido para el parser (PARAM(1.5) parsea);
            // que no exista lo dice el analizador, no la sintaxis
            'referencia con argumento que no es código' => ['PARAM(+)', 'DSL_ARGUMENTOS_INVALIDOS'],
        ];
    }

    public function test_los_alias_de_migracion_se_canonizan_al_leer(): void
    {
        // CANTIDAD y VALOR_UNITARIO se reconocen pero el nodo guarda la forma canónica
        $n = $this->parser->parsear('CANTIDAD * VALOR_UNITARIO');
        self::assertSame('CANT', $n->a->v);
        self::assertSame('CANTIDAD', $n->a->alias, 'se conserva el alias para poder advertirlo');
        self::assertSame('VALUNI', $n->b->v);
    }

    public function test_conserva_el_texto_del_literal_para_no_perder_precision(): void
    {
        // El importe se evalúa desde el texto, no desde un float del lexer
        $n = $this->parser->parsear('CANT * VALUNI * 1.5');
        self::assertSame('1.5', $n->b->v);
    }

    public function test_la_precedencia_es_la_del_contrato(): void
    {
        // O < Y < comparación < (+ -) < (* /)
        $n = $this->parser->parsear('1 + 2 * 3');
        self::assertSame(Nodo::ARITMETICA, $n->k);
        self::assertSame('+', $n->op);
        self::assertSame('*', $n->b->op, 'la multiplicación liga más fuerte que la suma');

        $n2 = $this->parser->parsear('CONC(BASICO) > 0 Y CONC(ANTIG) > 0 O CONC(ASIST) > 0');
        self::assertSame('O', $n2->op, 'O es el operador de menor precedencia');
        self::assertSame('Y', $n2->a->op);
    }
}
