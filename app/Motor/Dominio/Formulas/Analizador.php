<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Análisis de una expresión: referencias, alias, dependencias y unidades.
 * Port fiel de analizar() en src/dsl_v11.js.
 *
 * El análisis dimensional es lo que distingue a este DSL: suma y resta exigen
 * unidades compatibles, la multiplicación y la división las componen, una
 * magnitud nunca se compara contra un escalar, y el literal 0 es neutro
 * únicamente para unificar las ramas de SI().
 */
final class Analizador
{
    public function __construct(private readonly Parser $parser = new Parser) {}

    public function analizar(string $expresion, ContextoAnalisis $ctx): ResultadoAnalisis
    {
        $r = new ResultadoAnalisis;
        try {
            $ast = $this->parser->parsear($expresion);
        } catch (ErrorDsl $e) {
            $r->problema($e->codigo, $e->getMessage(), $e->posicion);

            return $r;
        }

        $r = new ResultadoAnalisis($ast);
        $r->unidad = $this->tipoDe($ast, $ctx, $r);

        return $r;
    }

    private function tipoDe(Nodo $n, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        switch ($n->k) {
            case Nodo::LITERAL:
                $r->literales[] = ['valor' => (string) $n->v, 'pos' => $n->pos];

                // El 0 sólo unifica ramas de SI(); fuera de ahí es un escalar común.
                return Dimension::escalar();

            case Nodo::RESERVADA:
                return $this->tipoDeReservada($n, $ctx, $r);

            case Nodo::REFERENCIA:
                return $this->tipoDeReferencia($n, $ctx, $r);

            case Nodo::NEGACION:
                return $this->tipoDe($n->a, $ctx, $r);

            case Nodo::ARITMETICA:
                return $this->tipoDeAritmetica($n, $ctx, $r);

            case Nodo::COMPARACION:
                $a = $this->tipoDe($n->a, $ctx, $r);
                $b = $this->tipoDe($n->b, $ctx, $r);
                $ia = $a->booleana ? Dimension::escalar() : $a;
                $ib = $b->booleana ? Dimension::escalar() : $b;
                if (! $ia->igualA($ib)) {
                    $r->problema('DSL_UNIDAD_INCOMPATIBLE', "No se puede comparar {$a} con {$b}");
                }

                return Dimension::booleano();

            case Nodo::LOGICO:
                $a = $this->tipoDe($n->a, $ctx, $r);
                $b = $this->tipoDe($n->b, $ctx, $r);
                if (! $a->booleana || ! $b->booleana) {
                    $r->problema('DSL_TIPO_INCOMPATIBLE', 'Los operadores Y / O trabajan sobre comparaciones');
                }

                return Dimension::booleano();

            case Nodo::FUNCION:
                return $this->tipoDeFuncion($n, $ctx, $r);
        }

        return Dimension::escalar();
    }

    private function tipoDeReservada(Nodo $n, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        if ($n->alias !== null) {
            $r->problema('DSL_ALIAS_NO_CANONICO', "'{$n->alias}' es un alias de migración: guardá '{$n->v}'");
        }

        if ($n->v === 'CANT') {
            if (! $ctx->tieneCantidad) {
                $r->problema('DSL_CANT_SIN_EXPRESION', 'Se usa CANT pero no hay expresión de cantidad');
            }
            if ($ctx->componente === ContextoAnalisis::CANTIDAD) {
                $r->problema('DSL_SINTAXIS_INVALIDA', 'CANT no puede usarse dentro de la propia expresión de cantidad');
            }

            return Dimension::deUnidad($ctx->unidadCantidad);
        }

        if ($n->v === 'VALUNI') {
            if (! $ctx->tieneValor) {
                $r->problema('DSL_VALUNI_SIN_EXPRESION', 'Se usa VALUNI pero no hay expresión de valor unitario');
            }
            if ($ctx->componente !== ContextoAnalisis::IMPORTE) {
                $r->problema('DSL_SINTAXIS_INVALIDA', 'VALUNI solo puede usarse en la expresión de importe');
            }

            return $ctx->unidadValor !== null ? Dimension::deUnidad($ctx->unidadValor) : Dimension::escalar();
        }

        return Dimension::escalar();
    }

    private function tipoDeAritmetica(Nodo $n, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        $a = $this->tipoDe($n->a, $ctx, $r);
        $b = $this->tipoDe($n->b, $ctx, $r);

        if ($a->booleana || $b->booleana) {
            $r->problema('DSL_TIPO_INCOMPATIBLE', 'No se puede operar aritméticamente con un valor lógico');

            return Dimension::escalar();
        }
        if ($n->op === '*') {
            return $a->por($b);
        }
        if ($n->op === '/') {
            return $a->dividido($b);
        }
        if (! $a->igualA($b)) {
            $r->problema('DSL_UNIDAD_INCOMPATIBLE', "No se puede sumar o restar {$a} con {$b}");
        }

        return $a;
    }

    private function tipoDeFuncion(Nodo $n, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        $tipos = array_map(fn (Nodo $x) => $this->tipoDe($x, $ctx, $r), $n->args);

        if ($n->nombre === 'SI') {
            if (! ($tipos[0] ?? Dimension::escalar())->booleana) {
                $r->problema('DSL_TIPO_INCOMPATIBLE', 'SI() espera una condición lógica en el primer argumento');
            }
            // El literal 0 unifica ramas: SI(cond, 0, importe_ARS) devuelve ARS.
            // Es la ÚNICA excepción: fuera de SI(), el 0 no es comodín dimensional.
            $cero1 = isset($n->args[1]) && $n->args[1]->esLiteralCero();
            $cero2 = isset($n->args[2]) && $n->args[2]->esLiteralCero();
            if ($cero1 && ! $cero2) {
                return $tipos[2];
            }
            if ($cero2 && ! $cero1) {
                return $tipos[1];
            }
            if (! $tipos[1]->igualA($tipos[2])) {
                $r->problema('DSL_UNIDAD_INCOMPATIBLE', "Las dos ramas de SI() deben devolver la misma unidad: {$tipos[1]} vs {$tipos[2]}");
            }

            return $tipos[1];
        }

        if ($n->nombre === 'REDONDEAR' || $n->nombre === 'ABS') {
            return $tipos[0];
        }

        // MIN y MAX comparan sus dos argumentos
        if (! $tipos[0]->igualA($tipos[1])) {
            $r->problema('DSL_UNIDAD_INCOMPATIBLE', "{$n->nombre}() compara {$tipos[0]} con {$tipos[1]}");
        }

        return $tipos[0];
    }

    private function tipoDeReferencia(Nodo $n, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        $prefijo = (string) $n->prefijo;
        $codigo = $n->codigo();
        if ($codigo === null) {
            $r->problema('DSL_ARGUMENTOS_INVALIDOS', "{$prefijo}(): falta el código de referencia");

            return Dimension::escalar();
        }
        $cat = $ctx->catalogos;
        $cantidadArgs = count($n->referencias);

        return match ($prefijo) {
            'PARAM' => $this->refParametro($codigo, $cantidadArgs, $cat, $r),
            'NOV' => $this->refNovedad($codigo, $cantidadArgs, $cat, $r),
            'VAR' => $this->refVariable($codigo, $cat, $r),
            'VALCAT' => $this->refValcat($codigo, $ctx, $r),
            'CONC' => $this->refConcepto($codigo, $ctx, $r),
            'BASE' => $this->refBase($codigo, $ctx, $r),
            'MOD' => $this->refModulo($codigo, $cat, $r),
            default => Dimension::escalar(),
        };
    }

    private function refParametro(string $codigo, int $args, CatalogoDsl $cat, ResultadoAnalisis $r): Dimension
    {
        if ($args > 1) {
            $r->problema('DSL_PARAM_INDEXADO_NO_CANONICO', "PARAM({$codigo}, ...) no es sintaxis canónica del DSL v1.1. Usá PARAM(COD) o MOD(COD.RESULTADO)");
        }
        $d = $cat->parametros[$codigo] ?? null;
        if ($d === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "El parámetro {$codigo} no existe");

            return Dimension::indeterminada();
        }
        $r->dependencias[] = ['tipo' => 'PARAMETRO', 'codigo' => $codigo, 'arista' => false];
        $unidad = Dimension::deUnidad($d['unidad'] ?? null);
        if ($unidad->indeterminada) {
            $r->sinUnidad[] = "PARAM({$codigo})";
        }

        return $unidad;
    }

    private function refNovedad(string $codigo, int $args, CatalogoDsl $cat, ResultadoAnalisis $r): Dimension
    {
        // F-2/P-6: la novedad tiene un único valor tipado; NOV(COD, CANT) ya no existe.
        if ($args !== 1) {
            $r->problema('DSL_ARGUMENTOS_INVALIDOS', "NOV({$codigo}) acepta exactamente un código. F-2/P-6 eliminó NOV(COD, CANT/IMPORTE)");
        }
        $d = $cat->novedades[$codigo] ?? null;
        if ($d === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "La novedad {$codigo} no existe");

            return Dimension::indeterminada();
        }
        if (! in_array($d['regla_agregacion'] ?? null, ['SUMA', 'UNICO'], true)) {
            $r->problema('ERROR_CONFIGURACION', "La novedad {$codigo} no declara regla_agregacion SUMA|UNICO");
        }
        $r->dependencias[] = ['tipo' => 'NOVEDAD', 'codigo' => $codigo, 'arista' => false];
        $unidad = Dimension::deUnidad($d['unidad'] ?? null);
        if ($unidad->indeterminada) {
            $r->sinUnidad[] = "NOV({$codigo})";
        }

        return $unidad;
    }

    private function refVariable(string $codigo, CatalogoDsl $cat, ResultadoAnalisis $r): Dimension
    {
        if (isset(Dsl::ALIAS[$codigo]) && ! isset($cat->variables[$codigo])) {
            $canonico = Dsl::ALIAS[$codigo];
            $r->problema('DSL_ALIAS_NO_CANONICO', "VAR({$codigo}) es un alias de migración: guardá VAR({$canonico})");
            $r->dependencias[] = ['tipo' => 'VARIABLE', 'codigo' => $canonico, 'arista' => false];
            $d0 = $cat->variables[$canonico] ?? null;

            return $d0 !== null ? Dimension::deUnidad($d0['unidad'] ?? null) : Dimension::escalar();
        }
        $d = $cat->variables[$codigo] ?? null;
        if ($d === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "La variable {$codigo} no existe en el catálogo canónico");

            return Dimension::indeterminada();
        }
        if (strlen($codigo) > Dsl::MAX_LARGO_IDENT) {
            $r->problema('DSL_IDENT_LARGO', "VAR({$codigo}) supera los ".Dsl::MAX_LARGO_IDENT.' caracteres');
        }
        $r->dependencias[] = ['tipo' => 'VARIABLE', 'codigo' => $codigo, 'arista' => false];
        $unidad = Dimension::deUnidad($d['unidad'] ?? null);
        if ($unidad->indeterminada) {
            $r->sinUnidad[] = "VAR({$codigo})";
        }

        return $unidad;
    }

    private function refValcat(string $codigo, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        // §9: en la v1.1 sólo se lee el valor categórico del propio concepto.
        if ($ctx->conceptoActual !== null && $codigo !== $ctx->conceptoActual) {
            $r->problema('DSL_VALCAT_AJENO', "VALCAT({$codigo}) no se puede usar en la fórmula de {$ctx->conceptoActual}: en la v1.1 solo se lee el VALCAT del propio concepto");
        }
        $d = $ctx->catalogos->valcat[$codigo] ?? null;
        if ($d === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "No hay valores por categoría declarados para {$codigo}");

            return Dimension::indeterminada();
        }
        $r->dependencias[] = ['tipo' => 'VALCAT', 'codigo' => $codigo, 'arista' => false];
        $unidad = Dimension::deUnidad($d['unidad'] ?? null);
        if ($unidad->indeterminada) {
            $r->sinUnidad[] = "VALCAT({$codigo})";
        }

        return $unidad;
    }

    private function refConcepto(string $codigo, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        if (! isset($ctx->catalogos->conceptos[$codigo])) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "El concepto {$codigo} no existe");

            return Dimension::base('ARS');
        }
        if ($ctx->conceptoActual !== null && $codigo === $ctx->conceptoActual) {
            $r->problema('DSL_AUTORREFERENCIA', "La fórmula de {$codigo} se referencia a sí misma");
        }
        $r->dependencias[] = ['tipo' => 'CONCEPTO', 'codigo' => $codigo, 'arista' => true];

        return Dimension::base('ARS');
    }

    private function refBase(string $codigo, ContextoAnalisis $ctx, ResultadoAnalisis $r): Dimension
    {
        $b = $ctx->catalogos->bases[$codigo] ?? null;
        if ($b === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "La base {$codigo} no existe");

            return Dimension::base('ARS');
        }
        // R13: si un concepto consume una base, no puede integrarla.
        if ($ctx->conceptoActual !== null && in_array($ctx->conceptoActual, $b['integrantes'] ?? [], true)) {
            $r->problema('DSL_CONSUME_E_INTEGRA', "{$ctx->conceptoActual} consume {$codigo} y además la integra: se calcularía sobre su propio resultado");
        }
        $r->dependencias[] = ['tipo' => 'BASE', 'codigo' => $codigo, 'arista' => true];

        return Dimension::base('ARS');
    }

    private function refModulo(string $codigo, CatalogoDsl $cat, ResultadoAnalisis $r): Dimension
    {
        $m = $cat->modulos[$codigo] ?? null;
        if ($m === null) {
            $r->problema('DSL_REFERENCIA_INEXISTENTE', "El resolvedor {$codigo} no existe");

            return Dimension::indeterminada();
        }
        if (! isset($m['dependencias']) || ! is_array($m['dependencias'])) {
            $r->problema('DSL_MOD_SIN_METADATA', "El resolvedor {$codigo} no declara dependencias");
        }
        $r->dependencias[] = ['tipo' => 'MODULO', 'codigo' => $codigo, 'arista' => true, 'dependencias' => $m['dependencias'] ?? []];
        $unidad = Dimension::deUnidad($m['unidad'] ?? null);
        if ($unidad->indeterminada) {
            $r->sinUnidad[] = "MOD({$codigo})";
        }

        return $unidad;
    }
}
