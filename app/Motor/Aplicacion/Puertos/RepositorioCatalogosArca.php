<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Aplicacion\DTO\CatalogoSicossFila;

/** Catálogos ARCA del motor (condiciones, modalidades, situaciones, actividades, localidades): lectura de códigos y carga idempotente. */
interface RepositorioCatalogosArca
{
    /** @return list<string> */
    public function codigosCondiciones(): array;

    /** @return list<string> */
    public function codigosModalidades(): array;

    /** @return list<string> */
    public function codigosActividades(): array;

    /** @return list<string> */
    public function codigosLocalidades(): array;

    /** @return list<string> */
    public function codigosSituaciones(): array;

    /**
     * Inserta las filas que no existan (nunca pisa las existentes). Devuelve cuántas se insertaron.
     *
     * @param  'condiciones'|'modalidades'|'situaciones'|'actividades'|'localidades'  $catalogo
     * @param  list<CatalogoSicossFila>  $filas
     */
    public function insertarFaltantes(string $catalogo, array $filas): int;
}
