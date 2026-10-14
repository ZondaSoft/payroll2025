<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Entidades\Variable;
use DateTimeImmutable;

/**
 * Las variables del DSL y sus valores por ámbito.
 *
 * El catálogo es cerrado en la práctica — lo fija el contrato del lenguaje y los
 * códigos tienen un largo máximo de 7 caracteres — pero los **valores** se cargan
 * por convenio, y son los que hacen falta para poner un convenio nuevo en marcha:
 * sin DIVJOR y HMJORN no hay feriado ni hora extra que se pueda calcular.
 */
interface RepositorioVariables
{
    /** @return list<Variable> */
    public function catalogo(): array;

    public function porCodigo(string $codigo): ?Variable;

    public function guardar(Variable $v, ?int $usuarioId = null): Variable;

    /** @return list<ValorVariable> todos los valores vigentes a la fecha, de todos los ámbitos */
    public function valoresVigentes(DateTimeImmutable $fecha): array;

    /** @return list<ValorVariable> todos los valores de una variable, vigentes o no */
    public function valoresDe(string $codigo): array;

    public function guardarValor(ValorVariable $v, ?int $usuarioId = null): ValorVariable;

    public function eliminarValor(string $codigo, int $idValor): void;
}
