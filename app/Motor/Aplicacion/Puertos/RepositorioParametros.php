<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Aplicacion\DTO\AmbitosLote;
use App\Motor\Dominio\Entidades\Parametro;
use App\Motor\Dominio\Entidades\ValorParametro;
use DateTimeInterface;

/** Parámetros y valores por alcance, cargados por lote. */
interface RepositorioParametros
{
    /** @return list<Parametro> */
    public function todos(): array;

    public function porCodigo(string $codigo): ?Parametro;

    /** @return list<ValorParametro> vigentes a la fecha, en los ámbitos del lote (GENERAL siempre) */
    public function valoresVigentes(DateTimeInterface $fecha, AmbitosLote $ambitos): array;

    /** @return list<ValorParametro> todos los valores de un parámetro (UI) */
    public function valoresDe(int $idParametro): array;

    public function guardarValor(ValorParametro $v, ?int $usuarioId = null): ValorParametro;
}
