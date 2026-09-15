<?php

namespace App\Motor\Aplicacion\DTO;

/** Fila cruda de novedad legacy (sue028s × columna), antes de normalizar. */
final readonly class NovedadOrigen
{
    public function __construct(
        public int $idOrigen,
        public string $periodoYyyymm,
        public int $legajoCodigo,
        public string $codNov,
        public string $columna,
        public string $cantidad,
    ) {}

    public function claveOrigen(): string
    {
        return $this->idOrigen.':'.$this->columna;
    }

    public function hash(): string
    {
        return hash('sha256', implode('|', [$this->periodoYyyymm, $this->legajoCodigo, $this->codNov, $this->columna, $this->cantidad]));
    }
}
