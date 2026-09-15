<?php

namespace App\Motor\Aplicacion\DTO;

final class ReporteSincronizacion
{
    public int $creadas = 0;

    public int $actualizadas = 0;

    public int $sinCambio = 0;

    public int $bajas = 0;

    /** @var list<string> */
    public array $avisos = [];

    /** @var list<string> */
    public array $conflictos = [];

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return get_object_vars($this);
    }
}
