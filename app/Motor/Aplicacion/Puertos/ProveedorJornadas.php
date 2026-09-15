<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Contratos\JornadaCanonica;

/** Adaptador de sue010s (jornada_id NULL → JornadaCanonica::sinDato()). */
interface ProveedorJornadas
{
    /** @return list<JornadaCanonica> */
    public function todas(): array;

    public function porId(?int $id): JornadaCanonica;
}
