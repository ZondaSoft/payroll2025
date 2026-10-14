<?php

namespace App\Motor\Dominio\Formulas;

/**
 * Valida una versión completa antes de publicarla. Port de validarVersion() en
 * src/dsl_v11.js.
 *
 * Dos modos, un solo análisis: en PUBLICACION todo problema bloquea; en EDICION
 * los avisos de migración y la unidad sin declarar se degradan a advertencia, para
 * que un borrador se pueda guardar a medio terminar sin perder el aviso.
 */
final class ValidadorVersion
{
    public const PUBLICACION = 'PUBLICACION';

    public const EDICION = 'EDICION';

    public function __construct(private readonly Analizador $analizador = new Analizador) {}

    public function validar(VersionFormula $version, CatalogoDsl $catalogos, string $modo = self::PUBLICACION): ResultadoValidacion
    {
        $r = new ResultadoValidacion($modo);

        if ($version->esModulo()) {
            if (trim((string) $version->codigoModulo) === '') {
                $r->error('DSL_MODULO_SIN_RESPONSABLE', 'El método MODULO exige un módulo responsable');
            }

            return $r->cerrar();
        }

        if (trim((string) $version->exprImporte) === '') {
            $r->error('DSL_IMPORTE_OBLIGATORIO', 'El importe es obligatorio');
        }

        $base = new ContextoAnalisis(
            catalogos: $catalogos,
            conceptoActual: $version->codigoConcepto,
            tieneCantidad: $version->tieneCantidad(),
            tieneValor: $version->tieneValor(),
            unidadCantidad: $version->unidadCantidad,
        );

        // Condición, cantidad y valor unitario, en ese orden: el importe necesita
        // conocer la dimensión real de VALUNI, que sale del análisis del valor.
        $dimensionValor = null;
        foreach ($this->bloques($version) as [$componente, $expresion]) {
            $analisis = $this->analizador->analizar($expresion, $base->para($componente));
            $r->registrar($componente, $analisis, $version);

            if ($componente === ContextoAnalisis::CONDICION && $analisis->ast !== null && ! $analisis->unidad->booleana) {
                $r->error('DSL_TIPO_INCOMPATIBLE', 'Condición: debe devolver un valor lógico');
            }
            if ($componente === ContextoAnalisis::CANTIDAD && $analisis->ast !== null && $version->unidadCantidad !== null
                && ! $analisis->unidad->igualA(Dimension::deUnidad($version->unidadCantidad))) {
                $r->error('DSL_UNIDAD_INCOMPATIBLE',
                    "Cantidad: la expresión devuelve {$analisis->unidad} y la unidad declarada es {$version->unidadCantidad}");
            }
            if ($componente === ContextoAnalisis::VALOR) {
                $dimensionValor = $analisis->unidad;
            }
        }

        if (trim((string) $version->exprImporte) !== '') {
            $analisis = $this->analizador->analizar(
                (string) $version->exprImporte,
                $base->para(ContextoAnalisis::IMPORTE, $dimensionValor)
            );
            $r->registrar(ContextoAnalisis::IMPORTE, $analisis, $version);
            $r->unidadImporte = $analisis->unidad;

            if ($analisis->ast !== null && $analisis->unidad->indeterminada) {
                // No se puede verificar el álgebra dimensional: es un problema del
                // catálogo, no de la fórmula. En EDICION se degrada a advertencia.
                $faltantes = $r->sinUnidad === [] ? 'alguna referencia' : implode(', ', $r->sinUnidad);
                $r->error('CFG_UNIDAD_SIN_DECLARAR',
                    "Importe: no se puede verificar que el resultado sea ARS porque el catálogo no declara la unidad de {$faltantes}");
            } elseif ($analisis->ast !== null && ! $analisis->unidad->igualA(Dimension::base('ARS'))) {
                $r->error('DSL_RESULTADO_NO_ARS', "Importe: la expresión devuelve {$analisis->unidad} y debe devolver ARS");
            }
        }

        $this->validarNovedades($version, $r);

        return $r->cerrar();
    }

    /** @return list<array{0: string, 1: string}> */
    private function bloques(VersionFormula $version): array
    {
        $bloques = [];
        if ($version->tieneCondicion()) {
            $bloques[] = [ContextoAnalisis::CONDICION, (string) $version->exprCondicion];
        }
        if ($version->tieneCantidad()) {
            $bloques[] = [ContextoAnalisis::CANTIDAD, (string) $version->exprCantidad];
        }
        if ($version->tieneValor()) {
            $bloques[] = [ContextoAnalisis::VALOR, (string) $version->exprValorUnitario];
        }

        return $bloques;
    }

    /**
     * F-2/P-6: las novedades se declaran por versión. La fórmula y la declaración
     * tienen que coincidir en los dos sentidos — una novedad usada sin declarar no
     * publica, y una declarada que ninguna expresión usa tampoco.
     */
    private function validarNovedades(VersionFormula $version, ResultadoValidacion $r): void
    {
        $usadas = [];
        foreach ($r->dependencias as $d) {
            if ($d['tipo'] === 'NOVEDAD') {
                $usadas[$d['codigo']] = true;
            }
        }

        $declaradas = [];
        foreach ($version->novedades as $codigo => $obligatoriedad) {
            if ($codigo === '') {
                $r->error('ERROR_CONFIGURACION', 'CONCEPTO_VERSION_NOVEDAD: falta codigo_novedad');

                continue;
            }
            if (! in_array($obligatoriedad, ['REQUERIDA', 'OPCIONAL'], true)) {
                $r->error('ERROR_CONFIGURACION', "CONCEPTO_VERSION_NOVEDAD {$codigo}: obligatoriedad debe ser REQUERIDA u OPCIONAL");
            }
            $declaradas[$codigo] = true;
        }

        foreach (array_keys($usadas) as $codigo) {
            if (! isset($declaradas[$codigo])) {
                $r->error('DSL_NOVEDAD_NO_DECLARADA',
                    "La fórmula usa NOV({$codigo}) pero la versión no la declara en CONCEPTO_VERSION_NOVEDAD");
            }
        }
        foreach (array_keys($declaradas) as $codigo) {
            if (! isset($usadas[$codigo])) {
                $r->error('DSL_NOVEDAD_DECLARADA_NO_USADA',
                    "La versión declara la novedad {$codigo} pero ninguna expresión usa NOV({$codigo})");
            }
        }
    }
}
