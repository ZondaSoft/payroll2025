<?php

namespace Tests\Unit\Motor\Formulas;

use App\Motor\Dominio\Formulas\CatalogoDsl;

/** Catálogo de los casos del §21 del contrato DSL, con las unidades que declara el libro. */
final class CatalogoDePrueba
{
    public static function crear(): CatalogoDsl
    {
        $conceptos = [];
        foreach (['BASICO', 'KMREC', 'KMSABDOM', 'AD562', 'CTRLCD', 'PERMFRB', 'ACDSIND562', 'ANTIG',
            'FERTRAB', 'FERTRABHS', 'HE50', 'HE100', 'ADTIT', 'ASIST', 'MANFON', 'PLUSPLUR', 'VIATKM',
            'PERMFRA', 'ADICNR20', 'PERNOCT', 'COMIDA', 'VIATESP', 'TOLHOR', 'ACTAFUT'] as $c) {
            $conceptos[$c] = [];
        }

        return new CatalogoDsl(
            conceptos: $conceptos,
            parametros: [
                'TARIFA_KM' => ['unidad' => 'ARS/KM'],
                'VALOR_CONTROL_CARGA_DESCARGA' => ['unidad' => 'ARS/UNIDAD'],
                'VALOR_PERMANENCIA_FR_B' => ['unidad' => 'ARS/UNIDAD'],
                'VALOR_PERMANENCIA_FR_A' => ['unidad' => 'ARS/UNIDAD'],
                'VALOR_PERNOCTADA_BASE' => ['unidad' => 'ARS/UNIDAD'],
                'PORC_ADIC_BASICO_5_6_2' => ['unidad' => 'PORCENTAJE'],
                'PORC_ACUERDO_EMPRESA_SINDICATO_5_6_2' => ['unidad' => 'PORCENTAJE'],
                'PORC_ADIC_NO_REMUNERATIVO' => ['unidad' => 'PORCENTAJE'],
                'PORC_ASISTENCIA' => ['unidad' => 'PORCENTAJE'],
                'PORC_MANEJO_FONDOS' => ['unidad' => 'PORCENTAJE'],
                'INASIS_MAX' => ['unidad' => 'DIAS'],
                'LLEGTAR_MAX' => ['unidad' => 'UNIDADES'],
                'SIN_UNIDAD' => ['unidad' => null],
            ],
            novedades: [
                'KM_REM' => ['unidad' => 'KM', 'regla_agregacion' => 'SUMA'],
                'KM_SAB_DOM' => ['unidad' => 'KM', 'regla_agregacion' => 'SUMA'],
                'KM_VIAT_NR' => ['unidad' => 'KM', 'regla_agregacion' => 'SUMA'],
                'CTRL_CARGA_DESC' => ['unidad' => 'UNIDADES', 'regla_agregacion' => 'SUMA'],
                'PERM_FR_B' => ['unidad' => 'UNIDADES', 'regla_agregacion' => 'SUMA'],
                'PERM_FR_A' => ['unidad' => 'UNIDADES', 'regla_agregacion' => 'SUMA'],
                'HE_50' => ['unidad' => 'HORAS', 'regla_agregacion' => 'SUMA'],
                'HE_100' => ['unidad' => 'HORAS', 'regla_agregacion' => 'SUMA'],
                'FERIADO_CANT' => ['unidad' => 'DIAS', 'regla_agregacion' => 'SUMA'],
                'FERIADO_HS' => ['unidad' => 'HORAS', 'regla_agregacion' => 'SUMA'],
                'PERNOCTADA' => ['unidad' => 'UNIDADES', 'regla_agregacion' => 'SUMA'],
                'INASIS' => ['unidad' => 'DIAS', 'regla_agregacion' => 'SUMA'],
                'LLEGTAR' => ['unidad' => 'UNIDADES', 'regla_agregacion' => 'SUMA'],
                'SIN_AGREGACION' => ['unidad' => 'DIAS'],
            ],
            variables: [
                'BASCAT' => ['unidad' => 'ARS'], 'HSPACT' => ['unidad' => 'HORAS'],
                'HSJORN' => ['unidad' => 'HORAS'], 'HDJORN' => ['unidad' => 'HORAS'],
                'HMJORN' => ['unidad' => 'HORAS'], 'DIVJOR' => ['unidad' => 'DIAS'],
                'DIASLIQ' => ['unidad' => 'DIAS'], 'ANTANIO' => ['unidad' => 'NUMERIC'],
            ],
            valcat: ['PLUSPLUR' => ['unidad' => 'PORCENTAJE'], 'TOLHOR' => ['unidad' => 'HORAS']],
            bases: [
                'BASE_ACUERDO_5_6_2' => ['integrantes' => ['KMREC', 'KMSABDOM', 'CTRLCD', 'PERMFRB']],
                'BASE_ANTIGUEDAD' => ['integrantes' => ['BASICO']],
                'BASE_HORA_NORMAL' => ['integrantes' => ['BASICO', 'ANTIG', 'ASIST', 'MANFON', 'AD562']],
                'BASE_NO_REMUNERATIVA' => ['integrantes' => ['VIATKM', 'PERMFRA']],
            ],
            modulos: ['ANTIGUEDAD.PORCENTAJE' => ['unidad' => 'PORCENTAJE', 'dependencias' => ['VAR(ANTANIO)']]],
        );
    }
}
