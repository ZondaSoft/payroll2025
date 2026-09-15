<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ReglaAplicabilidad;

/** Reglas de aplicabilidad (motor_concepto_aplicabilidad + motor_categoria_concepto como CATEGORIA). */
interface RepositorioAplicabilidad
{
    /** @return list<ReglaAplicabilidad> */
    public function deConcepto(string $codigoConcepto): array;

    public function guardar(ReglaAplicabilidad $r, ?int $usuarioId = null): ReglaAplicabilidad;

    public function eliminar(int $id, string $origen): void;
}
