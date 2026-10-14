<?php

namespace App\Motor\Dominio\Entidades;

/**
 * Una variable del DSL: lo que las fórmulas leen con VAR(). El catálogo declara su
 * unidad y, sobre todo, de dónde sale el valor — y eso determina quién la resuelve.
 *
 * CATEGORIA y RELACION_LABORAL no se copian a motor_valores_variable: BASCAT sale
 * de la escala de básicos y HSPACT de la jornada del trabajador. Lo que se carga
 * por convenio son los divisores (DIVJOR, HMJORN), que son normativos.
 */
final readonly class Variable
{
    public const CATEGORIA = 'CATEGORIA';

    public const RELACION_LABORAL = 'RELACION_LABORAL';

    public const CONVENIO = 'CONVENIO';

    public const PERIODO = 'PERIODO';

    public function __construct(
        public string $codigo,
        public string $descripcion,
        public ?string $unidad,
        public string $origen,
        public bool $activo = true,
    ) {}

    /** Las que se cargan como valor por ámbito; el resto las resuelve el contexto. */
    public function seCarga(): bool
    {
        return $this->origen === self::CONVENIO;
    }
}
