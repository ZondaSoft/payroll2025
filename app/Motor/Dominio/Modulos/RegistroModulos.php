<?php

namespace App\Motor\Dominio\Modulos;

use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Excepciones\MotorBloqueado;

/**
 * Los módulos disponibles, por código. Lo que no está registrado no se puede
 * invocar: MOD() sobre un código desconocido bloquea en vez de valer cero.
 */
final class RegistroModulos
{
    /** @var array<string, Modulo> */
    private array $modulos = [];

    /** @param list<Modulo> $modulos */
    public function __construct(array $modulos = [])
    {
        foreach ($modulos as $m) {
            $this->modulos[$m->codigo()] = $m;
        }
    }

    public static function porDefecto(): self
    {
        return new self([new ModuloAntiguedad]);
    }

    public function registrar(Modulo $modulo): void
    {
        $this->modulos[$modulo->codigo()] = $modulo;
    }

    public function tiene(string $codigo): bool
    {
        return isset($this->modulos[$codigo]);
    }

    public function obtener(string $codigo): Modulo
    {
        if (! isset($this->modulos[$codigo])) {
            throw MotorBloqueado::porque(CodigoBloqueo::ModuloSinResultado,
                "El módulo {$codigo} no está registrado en el motor: el motor bloquea.", "motor_modulos:{$codigo}");
        }

        return $this->modulos[$codigo];
    }

    /** Lo que el analizador necesita del catálogo: unidad y dependencias por módulo. */
    public function paraCatalogo(): array
    {
        $out = [];
        foreach ($this->modulos as $codigo => $m) {
            $out[$codigo] = ['unidad' => $m->unidad(), 'dependencias' => $m->dependencias()];
        }

        return $out;
    }
}
