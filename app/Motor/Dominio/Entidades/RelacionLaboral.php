<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoRelacion;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Contratación concreta del trabajador (entidad canónica). Los datos personales
 * viven en el legajo legacy; acá solo lo que hace falta para liquidar.
 */
final readonly class RelacionLaboral
{
    /**
     * @param  list<CategoriaVigente>  $categorias
     * @param  list<RegimenAsignado>  $regimenes
     * @param  list<SituacionRevista>  $situaciones
     */
    public function __construct(
        public ?int $id,
        public int $idLegajo,
        public int $empresaId,
        public string $legajo,
        public DateTimeImmutable $fechaIngreso,
        public ?DateTimeImmutable $fechaEgreso,
        public EstadoRelacion $estado,
        public ?string $cuil = null,
        public ?string $apellidoNombre = null,
        public ?DateTimeImmutable $fechaAntiguedad = null,
        public array $categorias = [],
        public ?EncuadreArca $encuadreArca = null,
        public array $regimenes = [],
        public array $situaciones = [],
    ) {}

    public function categoriaVigenteA(DateTimeInterface $fecha): ?CategoriaVigente
    {
        foreach ($this->categorias as $c) {
            if ($c->vigencia->contiene($fecha)) {
                return $c;
            }
        }

        return null;
    }

    /** @return list<RegimenAsignado> */
    public function regimenesVigentesA(DateTimeInterface $fecha): array
    {
        return array_values(array_filter($this->regimenes, fn (RegimenAsignado $r) => $r->vigencia->contiene($fecha)));
    }

    /** Fecha desde la que se cuenta la antigüedad (reconocida si existe, si no el ingreso). */
    public function fechaBaseAntiguedad(): DateTimeImmutable
    {
        return $this->fechaAntiguedad ?? $this->fechaIngreso;
    }

    /** Años completos de antigüedad al cierre del período. */
    public function aniosAntiguedadA(DateTimeInterface $fecha): int
    {
        $base = $this->fechaBaseAntiguedad();
        if ($fecha < $base) {
            return 0;
        }

        return (int) $base->diff($fecha)->y;
    }

    public function estaVigenteEn(DateTimeInterface $fecha): bool
    {
        return $this->estado !== EstadoRelacion::InconsistenteOrigen
            && $this->fechaIngreso <= $fecha
            && ($this->fechaEgreso === null || $this->fechaEgreso >= $fecha);
    }
}
