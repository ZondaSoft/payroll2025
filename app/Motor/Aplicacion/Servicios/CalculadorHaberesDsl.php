<?php

namespace App\Motor\Aplicacion\Servicios;

use App\Motor\Aplicacion\Puertos\RepositorioFormulas;
use App\Motor\Aplicacion\Puertos\RepositorioPerfiles;
use App\Motor\Aplicacion\Puertos\RepositorioValoresCategoria;
use App\Motor\Aplicacion\Puertos\RepositorioVariables;
use App\Motor\Dominio\Formulas\CatalogoDsl;
use App\Motor\Dominio\Formulas\CorridaFormulas;
use App\Motor\Dominio\Formulas\EstadoCorrida;
use App\Motor\Dominio\Formulas\ResolvedorDominio;
use App\Motor\Dominio\Formulas\VersionFormula;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Modulos\RegistroModulos;
use App\Motor\Dominio\Servicios\ResolutorBasico;
use App\Motor\Dominio\Servicios\ResolutorParametros;
use App\Motor\Dominio\Servicios\ResolutorPerfiles;
use App\Motor\Dominio\Servicios\ResolutorVariables;

/**
 * Calcula los haberes de un trabajador evaluando las fórmulas publicadas.
 *
 * Es el reemplazo de las estrategias tipadas para los conceptos con fórmula: el
 * perfil decide qué aplica y con qué variante, el grafo decide en qué orden, y el
 * DSL hace las cuentas. Las estrategias quedan sólo detrás de MOD(), que es donde
 * el contrato quiere el código: la mecánica que no se puede escribir como fórmula.
 */
final class CalculadorHaberesDsl
{
    public function __construct(
        private readonly FabricaCatalogoDsl $catalogos,
        private readonly RepositorioFormulas $formulas,
        private readonly RepositorioPerfiles $perfiles,
        private readonly RepositorioVariables $variables,
        private readonly RepositorioValoresCategoria $valoresCategoria,
        private readonly RegistroModulos $modulos,
        private readonly ResolutorPerfiles $resolutorPerfiles = new ResolutorPerfiles,
        private readonly CorridaFormulas $corrida = new CorridaFormulas,
    ) {}

    public function calcular(
        ContextoRelacionLaboral $ctx,
        ResolutorParametros $parametros,
        ResolutorBasico $basico,
    ): EstadoCorrida {
        $fecha = $ctx->fechaResolucion;
        $versiones = $this->formulas->vigentesA($fecha);
        $valcat = $this->valoresCategoria->vigentes($fecha);

        $resolvedor = new ResolvedorDominio(
            $ctx,
            $parametros,
            new ResolutorVariables($this->variables->valoresVigentes($fecha)),
            $basico,
            $this->modulos,
            $valcat,
        );

        $catalogos = $this->catalogos->construir($fecha);
        $decisiones = $this->resolutorPerfiles->decidir(
            $this->perfiles->vigentes($fecha),
            $this->conceptosACalcular($versiones, $catalogos),
            $ctx,
        );

        return $this->corrida->ejecutar($versiones, $catalogos, $resolvedor, $decisiones);
    }

    /**
     * El universo a decidir son los conceptos con fórmula publicada **más** los que
     * integran alguna base.
     *
     * Los segundos importan aunque no tengan fórmula: si la composición dice que
     * ADTIT integra BASE_HORA_NORMAL y nadie dice si corresponde al trabajador, la
     * base no se puede afirmar. Con la decisión del perfil, o se excluye con su
     * motivo o se bloquea por falta de fórmula, y las dos cosas son información.
     *
     * @param  list<VersionFormula>  $versiones
     * @return list<string>
     */
    private function conceptosACalcular(array $versiones, CatalogoDsl $catalogos): array
    {
        $codigos = array_map(fn (VersionFormula $v) => $v->codigoConcepto, $versiones);
        foreach ($catalogos->bases as $base) {
            foreach ($base['integrantes'] ?? [] as $codigo) {
                $codigos[] = $codigo;
            }
        }

        return array_values(array_unique($codigos));
    }
}
