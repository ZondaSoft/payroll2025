<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\ResultadoSimulacion;
use App\Motor\Aplicacion\DTO\SolicitudSimulacion;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\ProveedorJornadas;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\RepositorioAlicuotas;
use App\Motor\Aplicacion\Puertos\RepositorioBasesArca;
use App\Motor\Aplicacion\Puertos\RepositorioBasicos;
use App\Motor\Aplicacion\Puertos\RepositorioEncuadramientos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioModelosAntiguedad;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Aplicacion\Puertos\RepositorioTopes;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Aplicacion\Servicios\ArmadorContextoRelacion;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Liquidador\ContextoPeriodo;
use App\Motor\Dominio\Liquidador\MotorLiquidacion;
use App\Motor\Dominio\Liquidador\Resultado\Bloqueo;
use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;
use App\Motor\Dominio\Servicios\GateValidacion;

/**
 * Arma los contextos desde los puertos (una query por tabla, nunca por fila) y
 * ejecuta el motor para UNA relación laboral. No persiste el resultado (fase futura).
 *
 * Un solo camino de cálculo: los haberes salen de las fórmulas publicadas
 * (perfil + grafo + DSL, vía CalcularHaberes) y sobre ellos se arman las bases
 * ARCA, los topes, los aportes y las contribuciones contra la matriz S/N vigente.
 */
final class SimularLiquidacion
{
    public function __construct(
        private readonly RepositorioRelacionesLaborales $relaciones,
        private readonly RepositorioVersiones $versiones,
        private readonly RepositorioBasesArca $basesArca,
        private readonly RepositorioSubsistemas $subsistemas,
        private readonly RepositorioAlicuotas $alicuotas,
        private readonly RepositorioTopes $topes,
        private readonly RepositorioRegimenes $regimenes,
        private readonly RepositorioParametros $parametros,
        private readonly RepositorioBasicos $basicos,
        private readonly RepositorioModelosAntiguedad $modelosAntiguedad,
        private readonly RepositorioEncuadramientos $encuadramientos,
        private readonly RepositorioNovedades $novedades,
        private readonly ProveedorLegajos $legajos,
        private readonly ProveedorCategorias $categorias,
        private readonly ProveedorConvenios $convenios,
        private readonly ProveedorJornadas $jornadas,
        private readonly ProveedorEmpresas $empresas,
        private readonly ArmadorContextoRelacion $armador,
        private readonly CalcularHaberes $haberes,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly MotorLiquidacion $motor = new MotorLiquidacion,
    ) {}

    public function ejecutar(SolicitudSimulacion $s): ResultadoSimulacion
    {
        $fecha = $s->periodo->ultimoDia();
        $rl = $this->relaciones->porId($s->relacionLaboralId);
        if ($rl === null) {
            $liq = new Liquidacion($s->periodo, $s->relacionLaboralId, $s->tipoLiquidacion, $s->entorno);
            $liq->agregarBloqueo(new Bloqueo(CodigoBloqueo::RelacionInexistente, "La relación laboral #{$s->relacionLaboralId} no existe.", 'relacion', 'motor_relacion_laboral', null, true));

            return new ResultadoSimulacion($liq, ['relacion_laboral_id' => $s->relacionLaboralId]);
        }

        $periodo = new ContextoPeriodo($s->periodo, $s->tipoLiquidacion, $s->entorno, $this->versiones->vigentesA($fecha), $this->basesArca->todas(), $this->subsistemas->todos(),
            $this->subsistemas->aplicaciones(), $this->alicuotas->contribucionesVigentes($fecha), $this->alicuotas->aportesVigentes($fecha), $this->topes->delPeriodo($s->periodo), $this->regimenes->vigentes($fecha));

        // El mismo contexto que ve el cálculo por fórmulas: el convenio sale de la
        // categoría vigente y la discrepancia con el legajo queda como aviso.
        $ctx = $this->armador->armar($rl, $s->periodo, $s->diasLiquidados, $s->factorJornada, $s->condicionSindical);
        $cat = $rl->categoriaVigenteA($fecha);
        $categoria = $cat?->idCategoria === null ? null : ($this->categorias->porIds([$cat->idCategoria])[$cat->idCategoria] ?? null);
        $convenio = $ctx->codigoConvenio === null ? null : ($this->convenios->porCodigos([$ctx->codigoConvenio])[$ctx->codigoConvenio] ?? null);
        $modelo = $ctx->modeloAntiguedad;
        $enc = $ctx->encuadramiento;

        $corrida = $this->haberes->paraContexto($ctx, new GateValidacion($s->entorno));
        $liq = $this->motor->liquidar($periodo, $ctx, $this->parametros->valoresVigentes($fecha, $this->armador->ambitos($ctx)),
            $cat?->idCategoria === null ? [] : $this->basicos->porCategorias([$cat->idCategoria], $fecha),
            $corrida, $this->incidencias->todasVigentes($fecha));
        $empresa = $this->empresas->porId($rl->empresaId);

        return new ResultadoSimulacion($liq, [
            'relacion_laboral_id' => $rl->id, 'legajo' => $rl->legajo, 'nombre' => $rl->apellidoNombre, 'cuil' => $rl->cuil,
            'empresa' => $empresa?->razonSocial, 'empresa_id' => $rl->empresaId, 'categoria' => $categoria?->descripcion, 'codigo_categoria' => $cat?->codigoCategoria,
            'convenio' => $convenio?->descripcion, 'codigo_convenio' => $convenio?->codigo, 'jornada' => $ctx->jornada->descripcion,
            'antiguedad_anios' => $rl->aniosAntiguedadA($fecha), 'fecha_ingreso' => $rl->fechaIngreso->format('Y-m-d'),
            'encuadramiento' => $enc?->encuadramiento, 'modelo_antiguedad' => $modelo?->codigo, 'condicion_arca' => $rl->encuadreArca?->codigoCondicion,
            'regimenes' => array_map(fn ($r) => $r->codigoRegimen, $rl->regimenesVigentesA($fecha)), 'novedades' => count($ctx->novedadesPorCodigo),
            'periodo' => (string) $s->periodo, 'tipo_liquidacion' => $s->tipoLiquidacion->value, 'entorno' => $s->entorno->value, 'dias_liquidados' => $s->diasLiquidados,
        ]);
    }
}
