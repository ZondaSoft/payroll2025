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
    ) {}
}
