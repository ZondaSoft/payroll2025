<?php

namespace App\Motor\Dominio\Estrategias;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\EstrategiaCalculo as Estrategia;
use App\Motor\Dominio\Excepciones\MotorBloqueado;

/** Punto de extensión: acá se enchufaría un evaluador de DSL en una fase futura. */
final class RegistroEstrategias
{
    /** @var array<string, EstrategiaCalculo> */
    private array $estrategias;

    public function __construct()
    {
        $this->estrategias = [
            Estrategia::Basico->value => new Basico,
            Estrategia::Antiguedad->value => new Antiguedad,
            Estrategia::CantidadPorValor->value => new CantidadPorValor,
            Estrategia::PorcentajeSobreBase->value => new PorcentajeSobreBase,
            Estrategia::ImporteDirecto->value => new ImporteDirecto,
            Estrategia::ModuloSistema->value => new ModuloSistema,
        ];
    }

    public function registrar(string $clave, EstrategiaCalculo $estrategia): void
    {
        $this->estrategias[$clave] = $estrategia;
    }

    /** @throws MotorBloqueado si el concepto no tiene estrategia o no hay ejecutor (fail-safe §7 del mockup) */
    public function para(Concepto $c): EstrategiaCalculo
    {
        $clave = $c->estrategia?->value;
        if ($clave === null || ! isset($this->estrategias[$clave])) {
            throw MotorBloqueado::porque(CodigoBloqueo::EstrategiaSinEjecutor, "El concepto {$c->codigo} usa una estrategia sin ejecutor ('{$clave}'): el motor bloquea (fail-safe).", 'motor_conceptos', 'estrategia_calculo');
        }

        return $this->estrategias[$clave];
    }
}
