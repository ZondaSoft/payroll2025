<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Lo que el analizador necesita saber del mundo para validar una fórmula: qué
 * referencias existen y con qué unidad. No trae valores — eso es del evaluador.
 */
final readonly class CatalogoDsl
{
    /**
     * @param  array<string, array{unidad_cantidad?: ?string}>  $conceptos
     * @param  array<string, array{unidad?: ?string}>  $parametros
     * @param  array<string, array{unidad?: ?string, regla_agregacion?: ?string}>  $novedades
     * @param  array<string, array{unidad?: ?string}>  $variables
     * @param  array<string, array{unidad?: ?string}>  $valcat
     * @param  array<string, array{integrantes?: list<string>}>  $bases
     * @param  array<string, array{unidad?: ?string, dependencias?: list<string>}>  $modulos
     */
    public function __construct(
        public array $conceptos = [],
        public array $parametros = [],
        public array $novedades = [],
        public array $variables = [],
        public array $valcat = [],
        public array $bases = [],
        public array $modulos = [],
    ) {}
}
