<?php

namespace App\Motor\Dominio\Formulas;

/** Qué se está analizando: de qué concepto, qué componente y qué unidades declara la versión. */
final readonly class ContextoAnalisis
{
    public const CANTIDAD = 'cantidad';

    public const VALOR = 'valor';

    public const IMPORTE = 'importe';

    public const CONDICION = 'condicion';

    public function __construct(
        public CatalogoDsl $catalogos,
        public ?string $conceptoActual = null,
        public string $componente = self::IMPORTE,
        public bool $tieneCantidad = false,
        public bool $tieneValor = false,
        public ?string $unidadCantidad = null,
        public ?string $unidadValor = null,
        /**
         * Dimensión real de VALUNI, que se conoce recién después de analizar la
         * expresión de valor unitario: puede ser compuesta (ARS/HORAS) y entonces no
         * tiene un nombre de unidad del catálogo. Cuando viene, manda sobre $unidadValor.
         */
        public ?Dimension $dimensionValor = null,
    ) {}

    /** Mismo contexto, analizando otro componente. */
    public function para(string $componente, ?Dimension $dimensionValor = null): self
    {
        return new self($this->catalogos, $this->conceptoActual, $componente, $this->tieneCantidad,
            $this->tieneValor, $this->unidadCantidad, $this->unidadValor, $dimensionValor ?? $this->dimensionValor);
    }
}
