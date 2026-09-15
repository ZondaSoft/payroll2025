<?php

namespace App\Motor\Dominio\Liquidador;

use App\Motor\Dominio\Contratos\JornadaCanonica;
use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use App\Motor\Dominio\Entidades\ModeloAntiguedad;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

/** Lo que depende del trabajador: relación, categoría/convenio vigentes, novedades, jornada, encuadramiento de su empresa. */
final class ContextoRelacionLaboral
{
    /** @var list<Aviso> avisos acumulados durante la resolución */
    public array $avisos = [];

    /** @var array<string, list<Novedad>> por código de tipo de novedad */
    public array $novedadesPorCodigo = [];

    /**
     * @param  list<Novedad>  $novedades
     */
    public function __construct(
        public readonly RelacionLaboral $relacion,
        public readonly DateTimeImmutable $fechaResolucion,
        public readonly ?int $idCategoria,
        public readonly ?string $codigoCategoria,
        public readonly ?int $idConvenio,
        public readonly ?string $codigoConvenio,
        public readonly JornadaCanonica $jornada,
        public readonly ?EncuadramientoEmpresa $encuadramiento,
        public readonly ?ModeloAntiguedad $modeloAntiguedad,
        array $novedades,
        public readonly int $diasLiquidados = 30,
        public readonly BigDecimal|string $factorJornada = '1',
        public readonly string $condicionSindical = 'NO_INFORMADO',
    ) {
        foreach ($novedades as $n) {
            $this->novedadesPorCodigo[$n->codigoNovedad][] = $n;
        }
    }

    public function aniosAntiguedad(): int
    {
        return $this->relacion->aniosAntiguedadA($this->fechaResolucion);
    }

    /** Suma de cantidades de un tipo de novedad en el período (dos del mismo tipo se suman). */
    public function cantidadNovedad(string $codigo): ?BigDecimal
    {
        $lista = $this->novedadesPorCodigo[$codigo] ?? [];
        if ($lista === []) {
            return null;
        }
        $t = BigDecimal::zero();
        foreach ($lista as $n) {
            if ($n->cantidad !== null) {
                $t = $t->plus($n->cantidad);
            }
        }

        return $t;
    }

    /** Suma de valores (importes) de un tipo de novedad en el período. */
    public function valorNovedad(string $codigo): ?BigDecimal
    {
        $lista = $this->novedadesPorCodigo[$codigo] ?? [];
        if ($lista === []) {
            return null;
        }
        $t = BigDecimal::zero();
        foreach ($lista as $n) {
            if ($n->valor !== null) {
                $t = $t->plus($n->valor->valor);
            }
        }

        return $t;
    }

    public function factor(): BigDecimal
    {
        return BigDecimal::of($this->factorJornada);
    }
}
