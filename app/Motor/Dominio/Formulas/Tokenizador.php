<?php

namespace App\Motor\Dominio\Formulas;

/** Lexer del DSL v1.1. Port fiel de tokenizar() en src/dsl_v11.js. */
final class Tokenizador
{
    /** @return list<Token> */
    public function tokenizar(string $expresion): array
    {
        $tokens = [];
        $largo = strlen($expresion);
        $i = 0;

        while ($i < $largo) {
            $c = $expresion[$i];

            if (preg_match('/\s/', $c) === 1) {
                $i++;

                continue;
            }

            if (preg_match('/[0-9]/', $c) === 1) {
                $j = $i;
                while ($j < $largo && preg_match('/[0-9.]/', $expresion[$j]) === 1) {
                    $j++;
                }
                $texto = substr($expresion, $i, $j - $i);
                if (substr_count($texto, '.') > 1) {
                    throw ErrorDsl::en('DSL_SINTAXIS_INVALIDA', "Número mal formado: {$texto}", $i);
                }
                $tokens[] = new Token(Token::NUMERO, $texto, $i, $texto);
                $i = $j;

                continue;
            }

            if (preg_match('/[A-Za-z_]/', $c) === 1) {
                $j = $i;
                while ($j < $largo && preg_match('/[A-Za-z0-9_.]/', $expresion[$j]) === 1) {
                    $j++;
                }
                $texto = substr($expresion, $i, $j - $i);
                $tokens[] = new Token(Token::IDENTIFICADOR, strtoupper($texto), $i, $texto);
                $i = $j;

                continue;
            }

            $dos = substr($expresion, $i, 2);
            if (in_array($dos, ['!=', '>=', '<='], true)) {
                $tokens[] = new Token(Token::OPERADOR, $dos, $i);
                $i += 2;

                continue;
            }
            if ($dos === '<>') {
                throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', "'<>' no pertenece al DSL v1.1: usá '!='", $i);
            }

            if (str_contains('+-*/()=><,', $c)) {
                $tipo = match ($c) {
                    ',' => Token::COMA,
                    '(' => Token::PARENTESIS_ABRE,
                    ')' => Token::PARENTESIS_CIERRA,
                    default => Token::OPERADOR,
                };
                $tokens[] = new Token($tipo, $c, $i);
                $i++;

                continue;
            }

            if ($c === '"' || $c === "'") {
                throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', 'Los literales de texto no pertenecen al DSL v1.1', $i);
            }

            throw ErrorDsl::en('DSL_TOKEN_DESCONOCIDO', "Carácter no reconocido: {$c}", $i);
        }

        $tokens[] = new Token(Token::FIN, null, $largo);

        return $tokens;
    }
}
