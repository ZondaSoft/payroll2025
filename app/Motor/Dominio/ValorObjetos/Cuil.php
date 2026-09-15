<?php

namespace App\Motor\Dominio\ValorObjetos;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/** CUIL/CUIT de 11 dígitos con verificación del dígito de control (módulo 11). */
final readonly class Cuil implements JsonSerializable, Stringable
{
    private function __construct(public string $digitos) {}

    public static function de(string $texto): self
    {
        $d = preg_replace('/\D/', '', $texto) ?? '';
        if (strlen($d) !== 11) {
            throw new InvalidArgumentException("CUIL inválido: '{$texto}' (se esperan 11 dígitos).");
        }
        if (! self::verificadorValido($d)) {
            throw new InvalidArgumentException("CUIL con dígito verificador incorrecto: '{$texto}'.");
        }

        return new self($d);
    }

    public static function esValido(string $texto): bool
    {
        try {
            self::de($texto);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private static function verificadorValido(string $d): bool
    {
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += ((int) $d[$i]) * $pesos[$i];
        }
        $resto = 11 - ($suma % 11);
        $verificador = match ($resto) {
            11 => 0,
            10 => 9,
            default => $resto,
        };

        return $verificador === (int) $d[10];
    }

    /** 20-12345678-9 */
    public function formateado(): string
    {
        return substr($this->digitos, 0, 2).'-'.substr($this->digitos, 2, 8).'-'.substr($this->digitos, 10, 1);
    }

    public function __toString(): string
    {
        return $this->digitos;
    }

    public function jsonSerialize(): string
    {
        return $this->digitos;
    }
}
