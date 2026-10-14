<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Qué decidió el perfil sobre un concepto: si corresponde al trabajador y, cuando
 * el concepto tiene variantes, con cuál se liquida.
 *
 * La variante es parte de la decisión y no del concepto: el mismo BASICO se
 * calcula con JORNADA_PACTADA en el 371/03 y con LARGA_DISTANCIA en el 40/89, y
 * eso lo sabe el perfil, no la fórmula.
 *
 * La tercera forma es el bloqueo: dos perfiles del mismo nivel que se contradicen,
 * o ninguno que se pronuncie. El motor no elige por su cuenta ni omite en
 * silencio — deja el concepto bloqueado con su código.
 */
final readonly class DecisionPerfil
{
    private function __construct(
        public bool $aplica,
        public ?string $variante = null,
        public ?string $motivo = null,
        public ?string $nivel = null,
        public ?string $codigoBloqueo = null,
        public ?string $codigoPerfil = null,
    ) {}

    public static function aplica(?string $variante = null, ?string $nivel = null, ?string $codigoPerfil = null): self
    {
        return new self(true, $variante, null, $nivel, null, $codigoPerfil);
    }

    public static function noAplica(string $motivo, ?string $nivel = null, ?string $codigoPerfil = null): self
    {
        return new self(false, null, $motivo, $nivel, null, $codigoPerfil);
    }

    public static function bloqueada(string $codigoBloqueo, string $motivo, ?string $nivel = null): self
    {
        return new self(false, null, $motivo, $nivel, $codigoBloqueo);
    }

    public function estaBloqueada(): bool
    {
        return $this->codigoBloqueo !== null;
    }
}
