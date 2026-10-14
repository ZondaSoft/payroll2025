<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Catálogos del DSL v1.1 (contrato de Zonda). Port de src/dsl_v11.js.
 *
 * La versión vigente es la del instructivo de Oscar, no la de GitHub: F-2/P-6
 * eliminó NOV(COD, CANT) y ahora NOV() acepta exactamente un código, porque la
 * novedad tiene un único valor tipado y su obligatoriedad vive en la versión.
 */
final class Dsl
{
    public const VERSION = '1.1';

    /** Referencias tipadas: sus argumentos son códigos, no expresiones. */
    public const PREFIJOS = ['PARAM', 'NOV', 'VAR', 'VALCAT', 'CONC', 'BASE', 'MOD'];

    /** Función => cantidad exacta de argumentos. */
    public const FUNCIONES = ['SI' => 3, 'MIN' => 2, 'MAX' => 2, 'ABS' => 1, 'REDONDEAR' => 2];

    public const RESERVADAS = ['CANT', 'VALUNI'];

    /** Alias de migración: se reconocen al leer, nunca se publican (§8, §13). */
    public const ALIAS = [
        'CANTIDAD' => 'CANT',
        'VALOR_UNITARIO' => 'VALUNI',
        'BASICO_CATEGORIA' => 'BASCAT',
        'BASICO_CATEGORIA_VIGENTE' => 'BASCAT',
        'HORAS_SEMANALES_PACTADAS' => 'HSPACT',
        'HORAS_SEMANALES_JORNADA' => 'HSJORN',
        'HORAS_DIA_JORNADA' => 'HDJORN',
        'HORAS_MENSUALES_JORNADA' => 'HMJORN',
        'JORNADA_LABORAL' => 'HDJORN',
        'HORAS_TRABAJADAS' => 'HSPACT',
        'DIAS_LIQUIDADOS' => 'DIASLIQ',
        'ANTIGUEDAD_ANIOS' => 'ANTANIO',
    ];

    /** Tokens que la v1 admitía y la v1.1 deja fuera (§8). */
    public const FUERA_DE_V11 = ['AND', 'OR', 'NOT', 'IN', 'TRUE', 'FALSE', 'COALESCE', 'IFNULL', 'NVL'];

    /** §4: largo máximo de palabras reservadas y códigos de VAR. */
    public const MAX_LARGO_IDENT = 7;

    /** Códigos que sólo bloquean al publicar; al editar son advertencia. */
    public const CODIGOS_MIGRACION = ['DSL_ALIAS_NO_CANONICO', 'DSL_PARAM_INDEXADO_NO_CANONICO',
        'DSL_IDENTIFICADOR_LARGO', 'DSL_REFERENCIA_NO_CANONICA'];

    public const CODIGOS_SOLO_PUBLICACION = [...self::CODIGOS_MIGRACION, 'CFG_UNIDAD_SIN_DECLARAR'];
}
