<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\AmbitosLote;
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
use App\Motor\Aplicacion\Puertos\RepositorioModelosAntiguedad;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Aplicacion\Puertos\RepositorioTopes;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Liquidador\ContextoPeriodo;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\MotorLiquidacion;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\Liquidador\Resultado\Bloqueo;
use App\Motor\Dominio\Liquidador\Resultado\Liquidacion;

/**
 * Arma los contextos desde los puertos (una query por tabla, nunca por fila) y
 * ejecuta el motor para UNA relación laboral. No persiste el resultado (fase futura).
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

        // categoría → convenio (regla de la spec: el convenio sale de la categoría vigente)
        $cat = $rl->categoriaVigenteA($fecha);
        $categoria = $cat?->idCategoria === null ? null : ($this->categorias->porIds([$cat->idCategoria])[$cat->idCategoria] ?? null);
        $legajo = $this->legajos->porIds([$rl->idLegajo])[$rl->idLegajo] ?? null;
        $codigoConvenio = $categoria?->codigoConvenio ?? $legajo?->codigoConvenio;
        $convenio = $codigoConvenio === null ? null : ($this->convenios->porCodigos([$codigoConvenio])[$codigoConvenio] ?? null);
        $avisos = [];
        if ($categoria?->codigoConvenio !== null && $legajo?->codigoConvenio !== null && $categoria->codigoConvenio !== $legajo->codigoConvenio) {
            $avisos[] = new Aviso('CONVENIO_DISCREPANTE', "El convenio de la categoría ({$categoria->codigoConvenio}) difiere del convenio del legajo ({$legajo->codigoConvenio}); se usa el de la categoría.", 'sue001s.convenio');
        }
        $ambitos = new AmbitosLote($convenio === null ? [] : [$convenio->id], $cat?->idCategoria === null ? [] : [$cat->idCategoria], [$rl->empresaId], [$rl->id]);
        $modelo = $convenio === null ? null : ($this->modelosAntiguedad->asignadosAConvenios([$convenio->id], $fecha)[$convenio->id] ?? null);
        $enc = $this->encuadramientos->porEmpresas([$rl->empresaId], $fecha)[$rl->empresaId][0] ?? null;

        $ctx = new ContextoRelacionLaboral($rl, $fecha, $cat?->idCategoria, $cat?->codigoCategoria, $convenio?->id, $convenio?->codigo,
            $this->jornadas->porId($legajo?->jornadaId), $enc, $modelo, $this->novedades->porPeriodoYRelaciones($s->periodo, [$rl->id])[$rl->id] ?? [],
            $s->diasLiquidados, $s->factorJornada, $s->condicionSindical);
        $ctx->avisos = $avisos;

        $liq = $this->motor->liquidar($periodo, $ctx, $this->parametros->valoresVigentes($fecha, $ambitos), $cat?->idCategoria === null ? [] : $this->basicos->porCategorias([$cat->idCategoria], $fecha));
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
