<?php

namespace App\Motor\Dominio\Liquidador;

use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use App\Motor\Dominio\Entidades\BaseArca;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Entidades\RegimenContributivo;
use App\Motor\Dominio\Entidades\Subsistema;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use DateTimeImmutable;

/**
 * Todo lo que NO depende del trabajador y se comparte por el lote: período,
 * entorno, versiones vigentes (snapshots), normativa del período. Se carga una
 * sola vez (una query por tabla) y se pasa a cada liquidación.
 */
final readonly class ContextoPeriodo
{
    public DateTimeImmutable $fechaResolucion;

    /**
     * @param  array<string, ConceptoVersion>  $versiones  por código de concepto, vigentes al último día del período
     * @param  list<BaseArca>  $basesArca
     * @param  list<Subsistema>  $subsistemas
     * @param  list<SubsistemaAplicacion>  $aplicaciones
     * @param  list<AlicuotaContribucion>  $alicuotasContribuciones
     * @param  list<AlicuotaAporte>  $alicuotasAportes
     * @param  list<Tope>  $topes
     * @param  list<RegimenContributivo>  $regimenes
     */
    public function __construct(
        public Periodo $periodo,
        public TipoLiquidacion $tipoLiquidacion,
        public Entorno $entorno,
        public array $versiones,
        public array $basesArca,
        public array $subsistemas,
        public array $aplicaciones,
        public array $alicuotasContribuciones,
        public array $alicuotasAportes,
        public array $topes,
        public array $regimenes,
    ) {
        $this->fechaResolucion = $periodo->ultimoDia();
    }

    public function baseArca(string $codigo): ?BaseArca
    {
        foreach ($this->basesArca as $b) {
            if ($b->codigo === $codigo) {
                return $b;
            }
        }

        return null;
    }

    public function subsistema(string $codigo): ?Subsistema
    {
        foreach ($this->subsistemas as $s) {
            if ($s->codigo === $codigo) {
                return $s;
            }
        }

        return null;
    }

    /** @return array<string, SubsistemaAplicacion> por clave SUBSISTEMA|TIPO */
    public function aplicacionesPorClave(): array
    {
        $out = [];
        foreach ($this->aplicaciones as $a) {
            $out[$a->clave()] = $a;
        }

        return $out;
    }
}
