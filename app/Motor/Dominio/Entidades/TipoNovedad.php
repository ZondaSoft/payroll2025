<?php

namespace App\Motor\Dominio\Entidades;

final readonly class TipoNovedad
{
    public const SUMA = 'SUMA';

    public const UNICO = 'UNICO';

    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        /** NULL cuando el origen la declara PENDIENTE_UNIDAD: el motor bloquea antes de suponerla. */
        public ?string $unidad,
        public bool $activo = true,
        /**
         * Cómo se agregan dos novedades del mismo tipo en un período: SUMA (dos
         * partes de horas extras se acumulan) o UNICO (la segunda corrige a la
         * primera). Sin declararla, el DSL no publica una fórmula que use NOV().
         */
        public ?string $reglaAgregacion = self::SUMA,
        /** CANTIDAD | IMPORTE | PORCENTAJE: qué representa el valor cargado. */
        public ?string $tipoValor = null,
    ) {}
}
