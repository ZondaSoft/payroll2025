<?php

namespace App\Motor\Dominio\Formulas;

/** Token del lexer. `texto` conserva el original: los números se evalúan desde ahí, no desde un float. */
final readonly class Token
{
    public const NUMERO = 'NUM';

    public const IDENTIFICADOR = 'IDENT';

    public const OPERADOR = 'OP';

    public const COMA = 'COMA';

    public const PARENTESIS_ABRE = 'PAR_A';

    public const PARENTESIS_CIERRA = 'PAR_C';

    public const FIN = 'EOF';

    public function __construct(
        public string $tipo,
        public ?string $valor = null,
        public int $posicion = 0,
        public ?string $texto = null,
    ) {}

    public function es(string $tipo, ?string $valor = null): bool
    {
        return $this->tipo === $tipo && ($valor === null || $this->valor === $valor);
    }
}
