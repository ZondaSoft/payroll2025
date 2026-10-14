<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Nodo del AST. Se conserva la forma del contrato (`k` como discriminante) para
 * que el árbol siga siendo comparable con el del parser de referencia.
 *
 *   literal      v = texto del número tal como se escribió
 *   reservada    v = CANT | VALUNI (alias ya canonizado)
 *   ref          prefijo = PARAM|NOV|VAR|VALCAT|CONC|BASE|MOD, args = códigos
 *   funcion      nombre = SI|MIN|MAX|ABS|REDONDEAR, args = expresiones
 *   aritmetica   op = + - * /
 *   comparacion  op = = != > >= < <=
 *   logico       op = Y | O
 *   negacion     a = operando
 */
final readonly class Nodo
{
    public const LITERAL = 'literal';

    public const RESERVADA = 'reservada';

    public const REFERENCIA = 'ref';

    public const FUNCION = 'funcion';

    public const ARITMETICA = 'aritmetica';

    public const COMPARACION = 'comparacion';

    public const LOGICO = 'logico';

    public const NEGACION = 'negacion';

    /**
     * @param  list<Nodo>  $args  argumentos de una función
     * @param  list<array{tipoArg: string, v?: string, nodo?: Nodo, pos?: int}>  $referencias  argumentos de una referencia tipada
     */
    private function __construct(
        public string $k,
        public ?string $v = null,
        public ?string $op = null,
        public ?Nodo $a = null,
        public ?Nodo $b = null,
        public array $args = [],
        public ?string $prefijo = null,
        public ?string $nombre = null,
        public array $referencias = [],
        public int $pos = 0,
        public ?string $alias = null,
    ) {}

    public static function literal(string $texto, int $pos): self
    {
        return new self(self::LITERAL, v: $texto, pos: $pos);
    }

    public static function reservada(string $codigo, int $pos, ?string $alias = null): self
    {
        return new self(self::RESERVADA, v: $codigo, pos: $pos, alias: $alias);
    }

    /** @param list<array{tipoArg: string, v?: string, nodo?: Nodo, pos?: int}> $args */
    public static function referencia(string $prefijo, array $args, int $pos): self
    {
        return new self(self::REFERENCIA, prefijo: $prefijo, referencias: $args, pos: $pos);
    }

    /** @param list<Nodo> $args */
    public static function funcion(string $nombre, array $args, int $pos): self
    {
        return new self(self::FUNCION, nombre: $nombre, args: $args, pos: $pos);
    }

    public static function binaria(string $k, string $op, Nodo $a, Nodo $b): self
    {
        return new self($k, op: $op, a: $a, b: $b, pos: $a->pos);
    }

    public static function negacion(Nodo $a, int $pos): self
    {
        return new self(self::NEGACION, a: $a, pos: $pos);
    }

    /** El literal 0 es dimensionalmente neutro, pero sólo para unificar ramas de SI() (§18). */
    public function esLiteralCero(): bool
    {
        return $this->k === self::LITERAL && $this->v !== null && (float) $this->v === 0.0;
    }

    /** Código del primer argumento de una referencia tipada. */
    public function codigo(): ?string
    {
        $primero = $this->referencias[0] ?? null;

        return $primero !== null && ($primero['tipoArg'] ?? '') === 'codigo' ? ($primero['v'] ?? null) : null;
    }

    /** Atributo (segundo argumento) de una referencia: ya no existe en v1.1, se detecta para rechazarlo. */
    public function atributo(): ?string
    {
        $segundo = $this->referencias[1] ?? null;

        return $segundo !== null && ($segundo['tipoArg'] ?? '') === 'codigo' ? ($segundo['v'] ?? null) : null;
    }
}
