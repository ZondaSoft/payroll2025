<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Parser del DSL v1.1 → AST. Port fiel de parsear() en src/dsl_v11.js.
 *
 * Precedencia: O < Y < comparación < (+ -) < (* /) < unario.
 */
final class Parser
{
    /** @var list<Token> */
    private array $tokens = [];

    private int $p = 0;

    public function __construct(private readonly Tokenizador $tokenizador = new Tokenizador) {}

    public function parsear(string $expresion): Nodo
    {
        $this->tokens = $this->tokenizador->tokenizar($expresion);
        $this->p = 0;

        $raiz = $this->expresion();

        $actual = $this->ver();
        if ($actual->tipo === Token::IDENTIFICADOR && in_array($actual->valor, Dsl::FUERA_DE_V11, true)) {
            $sugerencia = match ($actual->valor) {
                'AND' => ": usá 'Y'",
                'OR' => ": usá 'O'",
                default => '',
            };
            throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', "'{$actual->valor}' no pertenece al DSL v1.1".$sugerencia, $actual->posicion);
        }
        if ($actual->tipo !== Token::FIN) {
            throw ErrorDsl::en('DSL_SINTAXIS_INVALIDA', 'Sobra contenido después del final de la expresión', $actual->posicion);
        }

        return $raiz;
    }

    private function ver(int $adelante = 0): Token
    {
        return $this->tokens[$this->p + $adelante] ?? new Token(Token::FIN);
    }

    private function comer(): Token
    {
        return $this->tokens[$this->p++];
    }

    private function esperar(string $tipo, ?string $valor = null): Token
    {
        $tk = $this->ver();
        if (! $tk->es($tipo, $valor)) {
            $esperado = $valor ?? $tipo;
            $apareció = $tk->valor ?? $tk->tipo;
            throw ErrorDsl::en('DSL_SINTAXIS_INVALIDA', "Se esperaba {$esperado} y apareció {$apareció}", $tk->posicion);
        }

        return $this->comer();
    }

    private function expresion(): Nodo
    {
        return $this->logicoO();
    }

    private function logicoO(): Nodo
    {
        $n = $this->logicoY();
        while ($this->ver()->es(Token::IDENTIFICADOR, 'O')) {
            $this->comer();
            $n = Nodo::binaria(Nodo::LOGICO, 'O', $n, $this->logicoY());
        }

        return $n;
    }

    private function logicoY(): Nodo
    {
        $n = $this->comparacion();
        while ($this->ver()->es(Token::IDENTIFICADOR, 'Y')) {
            $this->comer();
            $n = Nodo::binaria(Nodo::LOGICO, 'Y', $n, $this->comparacion());
        }

        return $n;
    }

    private function comparacion(): Nodo
    {
        $n = $this->suma();
        $tk = $this->ver();
        if ($tk->tipo === Token::OPERADOR && in_array($tk->valor, ['=', '!=', '>', '>=', '<', '<='], true)) {
            $op = (string) $this->comer()->valor;
            $n = Nodo::binaria(Nodo::COMPARACION, $op, $n, $this->suma());
        }

        return $n;
    }

    private function suma(): Nodo
    {
        $n = $this->multiplicacion();
        while ($this->ver()->tipo === Token::OPERADOR && in_array($this->ver()->valor, ['+', '-'], true)) {
            $op = (string) $this->comer()->valor;
            $n = Nodo::binaria(Nodo::ARITMETICA, $op, $n, $this->multiplicacion());
        }

        return $n;
    }

    private function multiplicacion(): Nodo
    {
        $n = $this->unario();
        while ($this->ver()->tipo === Token::OPERADOR && in_array($this->ver()->valor, ['*', '/'], true)) {
            $op = (string) $this->comer()->valor;
            $n = Nodo::binaria(Nodo::ARITMETICA, $op, $n, $this->unario());
        }

        return $n;
    }

    private function unario(): Nodo
    {
        if ($this->ver()->es(Token::OPERADOR, '-')) {
            $pos = $this->comer()->posicion;

            return Nodo::negacion($this->unario(), $pos);
        }

        return $this->primario();
    }

    private function primario(): Nodo
    {
        $tk = $this->ver();

        if ($tk->tipo === Token::NUMERO) {
            $this->comer();

            return Nodo::literal((string) $tk->texto, $tk->posicion);
        }

        if ($tk->tipo === Token::PARENTESIS_ABRE) {
            $this->comer();
            $n = $this->expresion();
            $this->esperar(Token::PARENTESIS_CIERRA);

            return $n;
        }

        if ($tk->tipo === Token::IDENTIFICADOR) {
            $nombre = (string) $tk->valor;
            $pos = $tk->posicion;
            $this->comer();

            if ($this->ver()->tipo === Token::PARENTESIS_ABRE) {
                return $this->llamada($nombre, $pos);
            }
            if (in_array($nombre, Dsl::FUERA_DE_V11, true)) {
                throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', "'{$nombre}' no pertenece al DSL v1.1", $pos);
            }
            if (isset(Dsl::ALIAS[$nombre])) {
                return Nodo::reservada(Dsl::ALIAS[$nombre], $pos, $nombre);
            }
            if (in_array($nombre, Dsl::RESERVADAS, true)) {
                return Nodo::reservada($nombre, $pos);
            }

            throw ErrorDsl::en('DSL_REFERENCIA_NO_CANONICA',
                "'{$nombre}' debe escribirse con su forma canónica: PARAM(...), NOV(...), VAR(...), VALCAT(...), CONC(...), BASE(...) o MOD(...)", $pos);
        }

        throw ErrorDsl::en('DSL_SINTAXIS_INVALIDA', 'Expresión incompleta', $tk->posicion);
    }

    private function llamada(string $nombre, int $pos): Nodo
    {
        $this->esperar(Token::PARENTESIS_ABRE);

        // Referencias tipadas: sus argumentos son códigos, no expresiones.
        if (in_array($nombre, Dsl::PREFIJOS, true)) {
            $args = [];
            while (true) {
                $tk = $this->ver();
                if ($tk->tipo === Token::IDENTIFICADOR && $this->ver(1)->tipo === Token::PARENTESIS_ABRE) {
                    // Llamada anidada dentro de una referencia: sólo aparece en configuraciones heredadas.
                    $args[] = ['tipoArg' => 'expr', 'nodo' => $this->primario()];
                } elseif ($tk->tipo === Token::IDENTIFICADOR) {
                    $this->comer();
                    $args[] = ['tipoArg' => 'codigo', 'v' => (string) $tk->valor, 'pos' => $tk->posicion];
                } elseif ($tk->tipo === Token::NUMERO) {
                    $this->comer();
                    $args[] = ['tipoArg' => 'codigo', 'v' => (string) $tk->texto, 'pos' => $tk->posicion];
                } else {
                    throw ErrorDsl::en('DSL_ARGUMENTOS_INVALIDOS', "{$nombre}(): se esperaba un código", $tk->posicion);
                }
                if ($this->ver()->tipo === Token::COMA) {
                    $this->comer();

                    continue;
                }
                break;
            }
            $this->esperar(Token::PARENTESIS_CIERRA);

            return Nodo::referencia($nombre, $args, $pos);
        }

        // Funciones del lenguaje
        if (! isset(Dsl::FUNCIONES[$nombre])) {
            throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', "La función '{$nombre}' no existe en el DSL v1.1", $pos);
        }
        $args = [];
        if ($this->ver()->tipo !== Token::PARENTESIS_CIERRA) {
            $args[] = $this->expresion();
            while ($this->ver()->tipo === Token::COMA) {
                $this->comer();
                $args[] = $this->expresion();
            }
        }
        $this->esperar(Token::PARENTESIS_CIERRA);

        $esperados = Dsl::FUNCIONES[$nombre];
        if (count($args) !== $esperados) {
            throw ErrorDsl::en('DSL_ARGUMENTOS_INVALIDOS',
                "{$nombre}() espera {$esperados} argumento(s) y recibió ".count($args), $pos);
        }

        return Nodo::funcion($nombre, $args, $pos);
    }
}
