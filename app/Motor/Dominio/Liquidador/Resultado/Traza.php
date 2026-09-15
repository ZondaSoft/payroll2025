<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

/** Explicación paso a paso de la corrida: parámetros resueltos, decisiones por concepto, etapas. */
final class Traza
{
    /** @var list<array{etapa: string, concepto: ?string, mensaje: string, datos: array<string, mixed>}> */
    private array $entradas = [];

    /** @var array<string, array<string, mixed>> codigo => {valor, alcance, ambito, vigencia, estado} */
    private array $parametros = [];

    public function registrar(string $etapa, string $mensaje, array $datos = [], ?string $concepto = null): void
    {
        $this->entradas[] = ['etapa' => $etapa, 'concepto' => $concepto, 'mensaje' => $mensaje, 'datos' => $datos];
    }

    public function parametroResuelto(string $codigo, array $datos): void
    {
        $this->parametros[$codigo] = $datos;
    }

    /** @return array{entradas: list<array<string, mixed>>, parametros: array<string, array<string, mixed>>} */
    public function aArray(): array
    {
        return ['entradas' => $this->entradas, 'parametros' => $this->parametros];
    }
}
