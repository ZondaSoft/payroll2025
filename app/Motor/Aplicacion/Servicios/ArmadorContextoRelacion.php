<?php

namespace App\Motor\Aplicacion\Servicios;

use App\Motor\Aplicacion\DTO\AmbitosLote;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\ProveedorJornadas;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\RepositorioEncuadramientos;
use App\Motor\Aplicacion\Puertos\RepositorioModelosAntiguedad;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\ValorObjetos\Periodo;

/**
 * Arma el contexto de un trabajador: su categoría y convenio vigentes, la jornada,
 * el modelo de antigüedad del convenio, el encuadramiento de la empresa y las
 * novedades del período.
 *
 * El convenio sale de la **categoría vigente**, no del legajo: cuando discrepan,
 * gana la categoría y queda el aviso. Es la regla de la spec y el motivo por el
 * que este armado está en un solo lugar — el simulador y el calculador de haberes
 * tienen que ver exactamente el mismo trabajador.
 */
final class ArmadorContextoRelacion
{
    public function __construct(
        private readonly ProveedorLegajos $legajos,
        private readonly ProveedorCategorias $categorias,
        private readonly ProveedorConvenios $convenios,
        private readonly ProveedorJornadas $jornadas,
        private readonly RepositorioModelosAntiguedad $modelosAntiguedad,
        private readonly RepositorioEncuadramientos $encuadramientos,
        private readonly RepositorioNovedades $novedades,
    ) {}

    public function armar(
        RelacionLaboral $rl,
        Periodo $periodo,
        int $diasLiquidados = 30,
        string|float $factorJornada = '1',
        string $condicionSindical = 'NO_INFORMADO',
        ?int $diasEfectivos = null,
    ): ContextoRelacionLaboral {
        $fecha = $periodo->ultimoDia();
        $cat = $rl->categoriaVigenteA($fecha);
        $categoria = $cat?->idCategoria === null ? null : ($this->categorias->porIds([$cat->idCategoria])[$cat->idCategoria] ?? null);
        $legajo = $this->legajos->porIds([$rl->idLegajo])[$rl->idLegajo] ?? null;
        $codigoConvenio = $categoria?->codigoConvenio ?? $legajo?->codigoConvenio;
        $convenio = $codigoConvenio === null ? null : ($this->convenios->porCodigos([$codigoConvenio])[$codigoConvenio] ?? null);

        $avisos = [];
        if ($categoria?->codigoConvenio !== null && $legajo?->codigoConvenio !== null && $categoria->codigoConvenio !== $legajo->codigoConvenio) {
            $avisos[] = new Aviso('CONVENIO_DISCREPANTE',
                "El convenio de la categoría ({$categoria->codigoConvenio}) difiere del convenio del legajo ({$legajo->codigoConvenio}); se usa el de la categoría.",
                'sue001s.convenio');
        }

        $modelo = $convenio === null ? null : ($this->modelosAntiguedad->asignadosAConvenios([$convenio->id], $fecha)[$convenio->id] ?? null);
        $enc = $this->encuadramientos->porEmpresas([$rl->empresaId], $fecha)[$rl->empresaId][0] ?? null;

        $ctx = new ContextoRelacionLaboral(
            $rl, $fecha, $cat?->idCategoria, $cat?->codigoCategoria, $convenio?->id, $convenio?->codigo,
            $this->jornadas->porId($legajo?->jornadaId), $enc, $modelo,
            $this->novedades->porPeriodoYRelaciones($periodo, [$rl->id])[$rl->id] ?? [],
            $diasLiquidados, $factorJornada, $condicionSindical, diasEfectivos: $diasEfectivos,
        );
        $ctx->avisos = $avisos;

        return $ctx;
    }

    /** Ámbitos para traer parámetros y valores de una sola consulta por tabla. */
    public function ambitos(ContextoRelacionLaboral $ctx): AmbitosLote
    {
        return new AmbitosLote(
            $ctx->idConvenio === null ? [] : [$ctx->idConvenio],
            $ctx->idCategoria === null ? [] : [$ctx->idCategoria],
            [$ctx->relacion->empresaId],
            [$ctx->relacion->id],
        );
    }
}
