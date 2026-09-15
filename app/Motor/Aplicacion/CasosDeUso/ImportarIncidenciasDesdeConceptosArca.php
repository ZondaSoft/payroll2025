<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\Puertos\ProveedorIncidenciasLegacy;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/**
 * Completa la matriz concepto × aplicación ARCA del motor a partir de las marcas
 * que payroll2025 ya tiene en conceptosarcas (validadas contra el portal ARCA),
 * para los conceptos del motor que tengan codigo_afip. Solo toca incidencias
 * PENDIENTE o inexistentes: nunca pisa una S/N ya decidida.
 */
final class ImportarIncidenciasDesdeConceptosArca
{
    public function __construct(
        private readonly ProveedorIncidenciasLegacy $legacy,
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioSubsistemas $subsistemas,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly UnidadDeTrabajo $uow,
        private readonly Reloj $reloj,
    ) {}

    /** @return array{conceptos_con_codigo_afip: int, sin_marca_legacy: list<string>, creadas: int, actualizadas: int, sin_cambio: int} */
    public function ejecutar(?int $idEmpresa = null, ?int $usuarioId = null): array
    {
        $marcas = $this->legacy->marcasPorCodigoAfip($idEmpresa);
        $aplicaciones = [];
        foreach ($this->subsistemas->aplicaciones() as $a) {
            $aplicaciones[$a->clave()] = $a;
        }
        $reporte = ['conceptos_con_codigo_afip' => 0, 'sin_marca_legacy' => [], 'creadas' => 0, 'actualizadas' => 0, 'sin_cambio' => 0];
        $hoy = $this->reloj->hoy();

        $this->uow->ejecutar(function () use (&$reporte, $marcas, $aplicaciones, $hoy, $usuarioId) {
            foreach ($this->conceptos->todos(true) as $c) {
                if ($c->codigoAfip === null) {
                    continue;
                }
                $reporte['conceptos_con_codigo_afip']++;
                if (! isset($marcas[$c->codigoAfip])) {
                    $reporte['sin_marca_legacy'][] = (string) $c->codigo;

                    continue;
                }
                $existentes = [];
                foreach ($this->incidencias->deConcepto((string) $c->codigo) as $i) {
                    if ($i->vigencia->contiene($hoy)) {
                        $existentes[$i->claveAplicacion()] = $i;
                    }
                }
                foreach ($aplicaciones as $clave => $ap) {
                    $incide = ($marcas[$c->codigoAfip][$clave] ?? false) ? Incidencia::Si : Incidencia::No;
                    $actual = $existentes[$clave] ?? null;
                    if ($actual === null) {
                        $this->incidencias->guardar(new IncidenciaArca(null, (string) $c->codigo, $ap->id, $ap->codigoSubsistema, $ap->tipoAplicacion, $incide,
                            Vigencia::abiertaDesde($c->vigencia->desde), EstadoValidacion::SinConfirmar), $usuarioId);
                        $reporte['creadas']++;
                    } elseif ($actual->incidencia === Incidencia::Pendiente) {
                        $this->incidencias->guardar(new IncidenciaArca($actual->id, $actual->codigoConcepto, $actual->idSubsistemaAplicacion, $actual->codigoSubsistema,
                            $actual->tipoAplicacion, $incide, $actual->vigencia, EstadoValidacion::SinConfirmar), $usuarioId);
                        $reporte['actualizadas']++;
                    } else {
                        $reporte['sin_cambio']++;
                    }
                }
            }
        });

        return $reporte;
    }
}
