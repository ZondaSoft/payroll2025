<?php

namespace App\Motor\Aplicacion\DTO;

final class ReporteNormalizacion
{
    public int $creadas = 0;

    public int $actualizadas = 0;

    public int $sinCambio = 0;

    public int $anuladas = 0;

    public int $ignoradas = 0;

    /** @var array<string, int> cod_nov => cantidad de filas */
    public array $sinMapeo = [];

    /** @var array<int, int> legajo => cantidad de filas */
    public array $sinRelacion = [];

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return get_object_vars($this);
    }
}
