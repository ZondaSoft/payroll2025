<?php

namespace App\Motor\Aplicacion\DTO;

final class ReporteIntegridad
{
    /** @var list<array{codigo: string, exigencia: string, descripcion: string, estado: string, detalle: string}> */
    public array $conceptosMinimos = [];

    /** @var list<list<string>> ciclos detectados entre bases de Zonda */
    public array $circularidades = [];

    /** @var array<string, int> tabla => cantidad */
    public array $sinDato = [];

    /** @var array<string, int> */
    public array $sinConfirmar = [];

    public int $incidenciasPendientes = 0;

    public int $conceptosSinVersion = 0;

    /** @var array<string, int> cod_nov => usos */
    public array $novedadesSinMapeo = [];

    public int $legajosActivosSinRelacion = 0;

    /** @var list<array{codigo: string, nivel: string, mensaje: string, concepto: string}> */
    public array $validacionesConceptos = [];

    public function habilitadoParaLiquidar(): bool
    {
        $obligatoriosFaltantes = array_filter($this->conceptosMinimos, fn ($c) => $c['exigencia'] === 'OBLIGATORIO' && $c['estado'] !== 'OK');

        return $obligatoriosFaltantes === [] && $this->circularidades === [];
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return get_object_vars($this) + ['habilitado' => $this->habilitadoParaLiquidar()];
    }
}
