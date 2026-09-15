<?php

namespace App\Motor\Dominio\Entidades;

use Brick\Math\BigDecimal;

/** Traducción de un código de novedad legacy (sue031s) × columna de sue028s a un tipo de novedad del motor. */
final readonly class MapeoNovedad
{
    public function __construct(
        public ?int $id,
        public string $codNov,
        public string $columnaOrigen,
        public string $codigoTipoNovedad,
        public ?string $unidad,
        public bool $esImporte,
        public BigDecimal $factor,
        public string $accion,
        public bool $activo = true,
    ) {}

    public function clave(): string
    {
        return $this->codNov.'|'.$this->columnaOrigen;
    }

    public function ignora(): bool
    {
        return $this->accion === 'IGNORAR';
    }
}
