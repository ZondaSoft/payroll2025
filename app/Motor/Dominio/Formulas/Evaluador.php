<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Evalúa un AST del DSL con los valores que entrega el resolvedor.
 * Port de crearEvaluador() en src/dsl_v11.js, con una diferencia deliberada:
 * acá la aritmética es BigDecimal, no coma flotante.
 *
 * Toda división lleva escala y modo de redondeo explícitos (si no, BigDecimal
 * lanza RoundingNecessaryException); la escala intermedia es 6, como fija el
 * criterio del motor, y el redondeo final lo decide la regla del concepto.
 */
final class Evaluador
{
    public const ESCALA = 6;

    /** @var array<string, BigDecimal> CANT y VALUNI mientras se evalúa el importe */
    private array $reservadas = [];

    public function __construct(
        private readonly CatalogoDsl $catalogos,
        private readonly ResolvedorDsl $resolvedor,
        private readonly TrazaFormula $traza = new TrazaFormula,
    ) {}

    public function traza(): TrazaFormula
    {
        return $this->traza;
    }

    /** @param array<string, BigDecimal> $reservadas valores de CANT / VALUNI */
    public function evaluar(Nodo $nodo, array $reservadas = []): BigDecimal
    {
        $this->reservadas = $reservadas;
        $valor = $this->ev($nodo);
        if (is_bool($valor)) {
            throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', 'La expresión resultó en un valor lógico y se esperaba un número');
        }

        return $valor;
    }

    /** @param array<string, BigDecimal> $reservadas */
    public function condicion(Nodo $nodo, array $reservadas = []): bool
    {
        $this->reservadas = $reservadas;
        $valor = $this->ev($nodo);
        if (! is_bool($valor)) {
            throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', 'La condición no resultó en un valor lógico');
        }

        return $valor;
    }

    private function ev(Nodo $n): BigDecimal|bool
    {
        return match ($n->k) {
            Nodo::LITERAL => $this->literal($n),
            Nodo::RESERVADA => $this->reservada($n),
            Nodo::NEGACION => $this->numero($this->ev($n->a))->negated(),
            Nodo::ARITMETICA => $this->aritmetica($n),
            Nodo::COMPARACION => $this->comparacion($n),
            Nodo::LOGICO => $this->logico($n),
            Nodo::FUNCION => $this->funcion($n),
            Nodo::REFERENCIA => $this->referencia($n),
            default => throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "Nodo no evaluable: {$n->k}"),
        };
    }

    private function literal(Nodo $n): BigDecimal
    {
        $this->traza->literal((string) $n->v);

        return BigDecimal::of((string) $n->v);
    }

    private function reservada(Nodo $n): BigDecimal
    {
        $codigo = (string) $n->v;
        if (! isset($this->reservadas[$codigo])) {
            throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "{$codigo} no está definido en esta fórmula");
        }

        return $this->reservadas[$codigo];
    }

    private function numero(BigDecimal|bool $v): BigDecimal
    {
        if (is_bool($v)) {
            throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', 'Se esperaba un número y apareció un valor lógico');
        }

        return $v;
    }

    private function aritmetica(Nodo $n): BigDecimal
    {
        $a = $this->numero($this->ev($n->a));
        $b = $this->numero($this->ev($n->b));

        return match ($n->op) {
            '+' => $a->plus($b),
            '-' => $a->minus($b),
            '*' => $a->multipliedBy($b),
            '/' => $b->isZero()
                ? throw BloqueoDsl::porque('DIVISION_CERO', 'División por cero')
                : $a->dividedBy($b, self::ESCALA, RoundingMode::HALF_UP),
            default => throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "Operador aritmético desconocido: {$n->op}"),
        };
    }

    private function comparacion(Nodo $n): bool
    {
        $a = $this->numero($this->ev($n->a));
        $b = $this->numero($this->ev($n->b));
        $c = $a->compareTo($b);

        return match ($n->op) {
            '=' => $c === 0,
            '!=' => $c !== 0,
            '>' => $c > 0,
            '>=' => $c >= 0,
            '<' => $c < 0,
            '<=' => $c <= 0,
            default => false,
        };
    }

    private function logico(Nodo $n): bool
    {
        // SI() hace short-circuit (§3); Y / O evalúan ambos lados para que un dato
        // faltante del otro operando no pase inadvertido.
        $a = $this->ev($n->a);
        $b = $this->ev($n->b);
        if (! is_bool($a) || ! is_bool($b)) {
            throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', 'Los operadores Y / O trabajan sobre comparaciones');
        }

        return $n->op === 'Y' ? ($a && $b) : ($a || $b);
    }

    private function funcion(Nodo $n): BigDecimal|bool
    {
        if ($n->nombre === 'SI') {
            $cond = $this->ev($n->args[0]);
            if (! is_bool($cond)) {
                throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', 'SI() espera una condición lógica');
            }

            return $cond ? $this->ev($n->args[1]) : $this->ev($n->args[2]);
        }

        return match ($n->nombre) {
            'MIN' => $this->extremo($n, menor: true),
            'MAX' => $this->extremo($n, menor: false),
            'ABS' => $this->numero($this->ev($n->args[0]))->abs(),
            'REDONDEAR' => $this->numero($this->ev($n->args[0]))
                ->toScale((int) $this->numero($this->ev($n->args[1]))->toInt(), RoundingMode::HALF_UP),
            default => throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "Función no implementada: {$n->nombre}"),
        };
    }

    private function extremo(Nodo $n, bool $menor): BigDecimal
    {
        $a = $this->numero($this->ev($n->args[0]));
        $b = $this->numero($this->ev($n->args[1]));
        $aEsMenor = $a->compareTo($b) <= 0;

        return ($menor === $aEsMenor) ? $a : $b;
    }

    private function referencia(Nodo $n): BigDecimal
    {
        $codigo = $n->codigo() ?? throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "{$n->prefijo}(): falta el código");

        return match ($n->prefijo) {
            'PARAM' => $this->refParametro($codigo),
            'NOV' => $this->refNovedad($codigo),
            'VAR' => $this->refVariable($codigo),
            'VALCAT' => $this->refValcat($codigo),
            'CONC' => $this->refConcepto($codigo),
            'BASE' => $this->refBase($codigo),
            'MOD' => $this->refModulo($codigo),
            default => throw BloqueoDsl::porque('RESULTADO_NO_NUMERICO', "Prefijo no implementado: {$n->prefijo}"),
        };
    }

    private function refParametro(string $codigo): BigDecimal
    {
        $r = $this->resolvedor->parametro($codigo);
        $this->traza->referencia("PARAM({$codigo})", ['valor' => (string) $r['valor'], 'alcance' => $r['alcance'] ?? null, 'vigencia' => $r['vigencia'] ?? null]);

        return $r['valor'];
    }

    private function refNovedad(string $codigo): BigDecimal
    {
        $r = $this->resolvedor->novedad($codigo);
        $existe = (bool) ($r['existe'] ?? false);
        // Una novedad OPCIONAL ausente vale cero tipado; si fuera REQUERIDA, quien
        // orquesta ya dejó el concepto en NO_APLICA antes de llegar acá.
        $valor = $existe ? $r['valor'] : BigDecimal::zero();
        $this->traza->referencia("NOV({$codigo})", ['valor' => (string) $valor, 'existe' => $existe, 'unidad' => $r['unidad'] ?? null]);

        return $valor;
    }

    private function refVariable(string $codigo): BigDecimal
    {
        // El alias se resuelve al canónico sólo si el catálogo no declara el alias.
        $canonico = isset($this->catalogos->variables[$codigo]) ? $codigo : (Dsl::ALIAS[$codigo] ?? $codigo);
        $r = $this->resolvedor->variable($canonico);
        $this->traza->referencia("VAR({$canonico})", ['valor' => (string) $r['valor']]);

        return $r['valor'];
    }

    private function refValcat(string $codigo): BigDecimal
    {
        $r = $this->resolvedor->valcat($codigo);
        $this->traza->referencia("VALCAT({$codigo})", ['valor' => (string) $r['valor'], 'categoria' => $r['categoria'] ?? null, 'vigencia' => $r['vigencia'] ?? null]);

        return $r['valor'];
    }

    private function refConcepto(string $codigo): BigDecimal
    {
        $valor = $this->resolvedor->concepto($codigo);
        $this->traza->referencia("CONC({$codigo})", ['valor' => (string) $valor]);

        return $valor;
    }

    private function refBase(string $codigo): BigDecimal
    {
        $valor = $this->resolvedor->base($codigo);
        $this->traza->referencia("BASE({$codigo})", ['valor' => (string) $valor]);

        return $valor;
    }

    private function refModulo(string $codigo): BigDecimal
    {
        $r = $this->resolvedor->modulo($codigo);
        $this->traza->referencia("MOD({$codigo})", ['valor' => (string) $r['valor'], 'version' => $r['version'] ?? null]);

        return $r['valor'];
    }
}
