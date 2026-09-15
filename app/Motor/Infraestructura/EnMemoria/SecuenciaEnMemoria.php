<?php

namespace App\Motor\Infraestructura\EnMemoria;

use DateTimeInterface;

/** Utilidades comunes de los repositorios en memoria (tests y fixtures). */
trait SecuenciaEnMemoria
{
    private int $secuencia = 1000;

    private function nuevoId(): int
    {
        return ++$this->secuencia;
    }

    /** @template T @param list<T> $items @return list<T> */
    private function vigentesA_(array $items, DateTimeInterface $fecha): array
    {
        return array_values(array_filter($items, fn ($i) => $i->vigencia->contiene($fecha)));
    }

    /** @template T @param list<T> $items @return list<T> */
    private function reemplazar(array $items, object $nuevo): array
    {
        $items = array_values(array_filter($items, fn ($i) => $i->id !== $nuevo->id));
        $items[] = $nuevo;

        return $items;
    }
}
