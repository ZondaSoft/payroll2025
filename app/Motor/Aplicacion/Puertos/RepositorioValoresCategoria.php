<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ValorCategoriaConcepto;
use DateTimeImmutable;

interface RepositorioValoresCategoria
{
    /**
     * Qué conceptos tienen valor por categoría y con qué unidad: la declaración de
     * motor_categoria_concepto, que es lo que el analizador necesita para saber si
     * VALCAT(COD) existe como referencia.
     *
     * Es distinto de tener valor: un concepto declarado puede no tener valor para la
     * categoría de un trabajador, y entonces el bloqueo es VALCAT_SIN_VALOR y no
     * DSL_REFERENCIA_INEXISTENTE.
     *
     * @return array<string, ?string> codigo_concepto => unidad
     */
    public function conceptosDeclarados(): array;

    /** @return list<ValorCategoriaConcepto> vigentes a la fecha, de todas las categorías */
    public function vigentes(DateTimeImmutable $fecha): array;
}
