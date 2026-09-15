<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\ConfiguracionMotor;
use App\Motor\Aplicacion\DTO\ReporteIntegridad;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\ProveedorNovedadesOrigen;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIfNovedadOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioIfRelacionOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Servicios\DetectorCircularidad;

/** Panel de integridad: qué falta para poder liquidar (no equivale a "habilitado": eso lo decide cada corrida con su gate). */
final class VerificarIntegridad
{
    public function __construct(
        private readonly ConfiguracionMotor $config,
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioVersiones $versiones,
        private readonly RepositorioBasesZonda $basesZonda,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly RepositorioTiposNovedad $tiposNovedad,
        private readonly RepositorioIfNovedadOrigen $ifNovedades,
        private readonly RepositorioIfRelacionOrigen $ifRelaciones,
        private readonly ProveedorLegajos $legajos,
        private readonly ProveedorNovedadesOrigen $novedadesOrigen,
        private readonly Reloj $reloj,
        private readonly ValidarConcepto $validar,
    ) {}

    public function ejecutar(): ReporteIntegridad
    {
        $r = new ReporteIntegridad;
        $hoy = $this->reloj->hoy();
        $conceptos = [];
        foreach ($this->conceptos->todos() as $c) {
            $conceptos[(string) $c->codigo] = $c;
        }

        // conceptos mínimos de sistema
        foreach ($this->config->conceptosMinimosSistema as $m) {
            $c = $conceptos[$m['codigo']] ?? null;
            $estado = $c === null ? 'FALTA' : (! $c->activo ? 'INACTIVO' : (! $c->estaPublicado() ? 'SIN_PUBLICAR' : 'OK'));
            $r->conceptosMinimos[] = ['codigo' => $m['codigo'], 'exigencia' => $m['exigencia']->value, 'descripcion' => $m['descripcion'], 'estado' => $estado,
                'detalle' => match ($estado) {
                    'FALTA' => 'El concepto no existe en el maestro.', 'INACTIVO' => 'Dado de baja lógica.', 'SIN_PUBLICAR' => 'Existe pero no está publicado.', default => ''
                }];
        }

        // circularidad entre bases
        $composicion = [];
        foreach ($this->basesZonda->composicionVigente($hoy) as $base => $lista) {
            $composicion[$base] = array_map(fn ($i) => $i->codigoConcepto, array_filter($lista, fn ($i) => $i->integra));
        }
        $consumos = [];
        foreach ($conceptos as $codigo => $c) {
            if ($c->codigoBaseZonda !== null && $c->activo) {
                $consumos[$codigo] = $c->codigoBaseZonda;
            }
        }
        $det = new DetectorCircularidad;
        foreach ($det->directas($composicion, $consumos) as $c) {
            $r->circularidades[] = [$c, $consumos[$c], $c];
        }
        $r->circularidades = array_merge($r->circularidades, $det->ciclos($composicion, $consumos));

        // incidencias PENDIENTE y conceptos sin versión
        foreach ($this->incidencias->todasVigentes($hoy) as $lista) {
            foreach ($lista as $i) {
                if ($i->incidencia === Incidencia::Pendiente) {
                    $r->incidenciasPendientes++;
                }
            }
        }
        $r->conceptosSinVersion = count($this->versiones->conceptosSinVersion());

        // novedades sin mapear (códigos usados en el legacy vs mapeos cargados)
        $mapeados = [];
        foreach ($this->tiposNovedad->mapeos(true) as $m) {
            $mapeados[$m->codNov] = true;
        }
        foreach ($this->novedadesOrigen->codigosUsados() as $cod => $n) {
            if (! isset($mapeados[$cod])) {
                $r->novedadesSinMapeo[$cod] = $n;
            }
        }
        arsort($r->novedadesSinMapeo);

        // legajos activos sin relación laboral
        $vinculados = array_flip($this->ifRelaciones->mapaRelacionPorLegajo(SincronizarRelacionesLaborales::SISTEMA, SincronizarRelacionesLaborales::TABLA));
        foreach ($this->legajos->activos() as $l) {
            if (! isset($vinculados[$l->id])) {
                $r->legajosActivosSinRelacion++;
            }
        }

        // validaciones por concepto activo (solo errores y avisos relevantes)
        foreach ($conceptos as $codigo => $c) {
            if (! $c->activo) {
                continue;
            }
            foreach ($this->validar->ejecutar($c) as $v) {
                $r->validacionesConceptos[] = $v + ['concepto' => $codigo];
            }
        }

        // resumen de estados de validación de la IF de novedades
        $r->sinDato = $this->ifNovedades->resumenEstados();

        return $r;
    }
}
