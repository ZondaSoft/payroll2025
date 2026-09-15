<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\ReporteSincronizacion;
use App\Motor\Aplicacion\Puertos\ProveedorCatalogosSicoss;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioCatalogosArca;
use App\Motor\Aplicacion\Puertos\RepositorioIfRelacionOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioRegimenes;
use App\Motor\Aplicacion\Puertos\RepositorioRelacionesLaborales;
use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use App\Motor\Dominio\Contratos\LegajoCanonica;
use App\Motor\Dominio\Entidades\CategoriaVigente;
use App\Motor\Dominio\Entidades\EncuadreArca;
use App\Motor\Dominio\Entidades\IfRelacionOrigen;
use App\Motor\Dominio\Entidades\RegimenAsignado;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Entidades\SituacionRevista;
use App\Motor\Dominio\Enums\EstadoRelacion;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use DateTimeImmutable;

/**
 * Crea/actualiza las relaciones laborales canónicas a partir de los legajos
 * legacy (sue001s), idempotente por IF (ZONDA_GESTION, sue001s, id) + hash.
 *
 * Reglas de reconciliación (plan §5):
 *  - alta nueva → RL + categoría + encuadre ARCA + régimen GENERAL + situación del período + IF
 *  - cambio de categoría → se historiza (cierra la vigente el día anterior, abre otra SIN_CONFIRMAR)
 *  - cambio de empresa → NO automático: RL INCONSISTENTE_ORIGEN y conflicto reportado
 *  - baja → fecha_egreso + estado BAJA; reingreso (baja vuelve a NULL) → conflicto, no se reabre
 *  - cambio de sicoss_* / alta / antigüedad → se actualiza y vuelve a SIN_CONFIRMAR
 * El vínculo es por sue001s.id (no por número de legajo): sobrevive a renumeraciones.
 */
final class SincronizarRelacionesLaborales
{
    public const SISTEMA = 'ZONDA_GESTION';

    public const TABLA = 'sue001s';

    public const REGIMEN_GENERAL = 'GENERAL';

    public function __construct(
        private readonly ProveedorLegajos $legajos,
        private readonly ProveedorEmpresas $empresas,
        private readonly ProveedorCategorias $categorias,
        private readonly ProveedorCatalogosSicoss $sicoss,
        private readonly RepositorioCatalogosArca $catalogos,
        private readonly RepositorioRelacionesLaborales $relaciones,
        private readonly RepositorioIfRelacionOrigen $interfaz,
        private readonly RepositorioRegimenes $regimenes,
        private readonly UnidadDeTrabajo $uow,
        private readonly Reloj $reloj,
    ) {}

    public function ejecutar(?string $codigoEmpresa = null, ?Periodo $periodo = null, ?DateTimeImmutable $fechaEfectiva = null, ?int $usuarioId = null): ReporteSincronizacion
    {
        $reporte = new ReporteSincronizacion;
        $periodo ??= Periodo::deFecha($this->reloj->hoy());
        $fechaEfectiva ??= $periodo->primerDia();

        $legajos = $this->legajos->activos($codigoEmpresa);
        $empresas = [];
        foreach ($this->empresas->todas() as $e) {
            $empresas[$e->codigo] = $e;
        }
        $categorias = $this->categorias->porCodigos(array_values(array_unique(array_filter(array_map(fn ($l) => $l->codigoCategoria, $legajos)))));
        $zonaANumero = $this->sicoss->mapaZonaANumero();
        $catalogo = [
            'condiciones' => array_flip($this->catalogos->codigosCondiciones()),
            'modalidades' => array_flip($this->catalogos->codigosModalidades()),
            'actividades' => array_flip($this->catalogos->codigosActividades()),
            'localidades' => array_flip($this->catalogos->codigosLocalidades()),
            'situaciones' => array_flip($this->catalogos->codigosSituaciones()),
        ];
        $regimenGeneral = $this->regimenes->porCodigo(self::REGIMEN_GENERAL);
        if ($regimenGeneral === null) {
            $reporte->conflictos[] = 'No existe el régimen contributivo GENERAL: no se asigna régimen base.';
        }

        foreach (array_chunk($legajos, 200) as $lote) {
            $this->uow->ejecutar(function () use ($lote, $empresas, $categorias, $zonaANumero, $catalogo, $regimenGeneral, $periodo, $fechaEfectiva, $usuarioId, $reporte) {
                $claves = array_map(fn ($l) => (string) $l->id, $lote);
                $ifs = $this->interfaz->porClaves(self::SISTEMA, self::TABLA, $claves);
                $existentes = [];
                if ($ifs !== []) {
                    foreach ($this->relaciones->porIds(array_values(array_map(fn ($i) => $i->relacionLaboralId, $ifs))) as $rl) {
                        $existentes[$rl->id] = $rl;
                    }
                }

                foreach ($lote as $legajo) {
                    $empresa = $empresas[$legajo->codigoEmpresa ?? ''] ?? null;
                    if ($empresa === null) {
                        $reporte->conflictos[] = "Legajo {$legajo->codigo}: empresa '{$legajo->codigoEmpresa}' inexistente en sue086s; omitido.";

                        continue;
                    }
                    $if = $ifs[(string) $legajo->id] ?? null;

                    if ($if === null) {
                        $rl = $this->construirNueva($legajo, $empresa->id, $categorias, $zonaANumero, $catalogo, $regimenGeneral, $periodo, $fechaEfectiva, $reporte);
                        $rl = $this->relaciones->guardar($rl, $usuarioId);
                        $this->interfaz->guardar(new IfRelacionOrigen(null, $rl->id, self::SISTEMA, self::TABLA, (string) $legajo->id, (string) $legajo->codigo, $legajo->hash()));
                        $reporte->creadas++;

                        continue;
                    }

                    if ($if->hashOrigen === $legajo->hash()) {
                        $reporte->sinCambio++;

                        continue;
                    }

                    $rl = $existentes[$if->relacionLaboralId] ?? null;
                    if ($rl === null) {
                        $reporte->conflictos[] = "Legajo {$legajo->codigo}: la interfaz apunta a una relación laboral inexistente (#{$if->relacionLaboralId}).";

                        continue;
                    }
                    $rl = $this->reconciliar($rl, $legajo, $empresa->id, $categorias, $zonaANumero, $catalogo, $fechaEfectiva, $reporte);
                    $this->relaciones->guardar($rl, $usuarioId);
                    $this->interfaz->guardar(new IfRelacionOrigen($if->id, $if->relacionLaboralId, $if->sistemaOrigen, $if->tablaOrigen, $if->claveOrigen, (string) $legajo->codigo, $legajo->hash(), true));
                    $reporte->actualizadas++;
                }
            });
        }

        // Bajas: legajos con IF activa que ya no están entre los activos del origen
        $this->procesarBajas($codigoEmpresa, $legajos, $usuarioId, $reporte);

        return $reporte;
    }

    /** @param array<string, \App\Motor\Dominio\Contratos\CategoriaCanonica> $categorias */
    private function construirNueva(LegajoCanonica $l, int $empresaId, array $categorias, array $zonaANumero, array $catalogo, mixed $regimenGeneral, Periodo $periodo, DateTimeImmutable $fechaEfectiva, ReporteSincronizacion $reporte): RelacionLaboral
    {
        $ingreso = $l->fechaIngreso;
        if ($ingreso === null) {
            $ingreso = $fechaEfectiva;
            $reporte->avisos[] = "Legajo {$l->codigo}: sin fecha de alta en el origen; se usa {$fechaEfectiva->format('Y-m-d')}.";
        }
        $cat = $l->codigoCategoria !== null ? ($categorias[$l->codigoCategoria] ?? null) : null;
        if ($cat === null) {
            $reporte->avisos[] = "Legajo {$l->codigo}: categoría '{$l->codigoCategoria}' no encontrada en sue006s; la relación queda sin categoría.";
        }
        $regimenes = $regimenGeneral === null ? [] : [new RegimenAsignado(null, $regimenGeneral->id, $regimenGeneral->codigo, Vigencia::abiertaDesde($ingreso), EstadoValidacion::SinConfirmar)];

        return new RelacionLaboral(
            id: null, idLegajo: $l->id, empresaId: $empresaId, legajo: (string) $l->codigo, fechaIngreso: $ingreso, fechaEgreso: $l->fechaEgreso,
            estado: $l->fechaEgreso === null ? EstadoRelacion::Vigente : EstadoRelacion::Baja, cuil: $l->cuil, apellidoNombre: $l->apellidoNombre, fechaAntiguedad: $l->fechaAntiguedad,
            categorias: $cat === null ? [] : [new CategoriaVigente(null, $cat->id, $cat->codigo, Vigencia::abiertaDesde($ingreso), EstadoValidacion::SinConfirmar)],
            encuadreArca: $this->encuadre($l, $ingreso, $zonaANumero, $catalogo, $reporte),
            regimenes: $regimenes,
            situaciones: [new SituacionRevista(null, $periodo, 1, isset($catalogo['situaciones'][$l->sicossSituacion ?? '']) ? (string) $l->sicossSituacion : '1', 1, 31, 'Sincronización desde legajo')],
        );
    }

    /** @param array<string, \App\Motor\Dominio\Contratos\CategoriaCanonica> $categorias */
    private function reconciliar(RelacionLaboral $rl, LegajoCanonica $l, int $empresaId, array $categorias, array $zonaANumero, array $catalogo, DateTimeImmutable $fechaEfectiva, ReporteSincronizacion $reporte): RelacionLaboral
    {
        $estado = $rl->estado;
        if ($empresaId !== $rl->empresaId) {
            $estado = EstadoRelacion::InconsistenteOrigen;
            $reporte->conflictos[] = "Legajo {$l->codigo}: cambió de empresa en el origen (RL #{$rl->id}). Marcada INCONSISTENTE_ORIGEN: cerrar la relación y crear otra desde la pantalla.";
        }
        if ($l->fechaEgreso === null && $rl->fechaEgreso !== null) {
            $reporte->conflictos[] = "Legajo {$l->codigo}: la baja fue revertida en el origen; la relación #{$rl->id} sigue en BAJA (un reingreso es una relación nueva).";
        }

        $categorias_ = $rl->categorias;
        $cat = $l->codigoCategoria !== null ? ($categorias[$l->codigoCategoria] ?? null) : null;
        $vigente = $rl->categoriaVigenteA($fechaEfectiva);
        if ($cat !== null && ($vigente === null || $vigente->idCategoria !== $cat->id)) {
            $categorias_ = [];
            foreach ($rl->categorias as $c) {
                $categorias_[] = ($vigente !== null && $c->id === $vigente->id && $c->vigencia->esAbierta())
                    ? new CategoriaVigente($c->id, $c->idCategoria, $c->codigoCategoria, $c->vigencia->cerradaAntesDe($fechaEfectiva), $c->estadoValidacion)
                    : $c;
            }
            $categorias_[] = new CategoriaVigente(null, $cat->id, $cat->codigo, Vigencia::abiertaDesde($fechaEfectiva), EstadoValidacion::SinConfirmar);
            $reporte->avisos[] = "Legajo {$l->codigo}: cambio de categoría a {$cat->codigo} desde {$fechaEfectiva->format('Y-m-d')} (confirmar la fecha real).";
        }

        $encuadre = $this->encuadre($l, $rl->fechaIngreso, $zonaANumero, $catalogo, $reporte, $rl->encuadreArca);

        return new RelacionLaboral(
            id: $rl->id, idLegajo: $rl->idLegajo, empresaId: $rl->empresaId, legajo: (string) $l->codigo,
            fechaIngreso: $l->fechaIngreso ?? $rl->fechaIngreso, fechaEgreso: $l->fechaEgreso ?? $rl->fechaEgreso,
            estado: $l->fechaEgreso !== null && $estado === EstadoRelacion::Vigente ? EstadoRelacion::Baja : $estado,
            cuil: $l->cuil, apellidoNombre: $l->apellidoNombre, fechaAntiguedad: $l->fechaAntiguedad,
            categorias: $categorias_, encuadreArca: $encuadre, regimenes: $rl->regimenes, situaciones: $rl->situaciones,
        );
    }

    private function encuadre(LegajoCanonica $l, DateTimeImmutable $desde, array $zonaANumero, array $catalogo, ReporteSincronizacion $reporte, ?EncuadreArca $actual = null): EncuadreArca
    {
        $condicion = $l->sicossCondicion !== null && isset($catalogo['condiciones'][$l->sicossCondicion]) ? $l->sicossCondicion : '1';
        if ($l->sicossCondicion !== null && ! isset($catalogo['condiciones'][$l->sicossCondicion])) {
            $reporte->avisos[] = "Legajo {$l->codigo}: condición SICOSS '{$l->sicossCondicion}' no está en el catálogo; se usa 1.";
        }
        $modalidad = $l->sicossModalidad !== null && isset($catalogo['modalidades'][$l->sicossModalidad]) ? $l->sicossModalidad : '8';
        $actividad = $l->sicossActividad !== null && isset($catalogo['actividades'][$l->sicossActividad]) ? $l->sicossActividad : null;
        $numeroZona = $l->sicossLocalidad !== null ? ($zonaANumero[(int) $l->sicossLocalidad] ?? null) : null;
        $localidad = $numeroZona !== null && isset($catalogo['localidades'][$numeroZona]) ? $numeroZona : null;

        return new EncuadreArca($actual?->id, $condicion, null, $actividad, $modalidad, $localidad, $actual?->vigencia ?? Vigencia::abiertaDesde($desde), EstadoValidacion::SinConfirmar);
    }

    /** @param list<LegajoCanonica> $activos */
    private function procesarBajas(?string $codigoEmpresa, array $activos, ?int $usuarioId, ReporteSincronizacion $reporte): void
    {
        $mapa = $this->interfaz->mapaRelacionPorLegajo(self::SISTEMA, self::TABLA); // rl_id => sue001s.id
        $activosPorId = [];
        foreach ($activos as $l) {
            $activosPorId[$l->id] = true;
        }
        $candidatos = array_keys(array_filter($mapa, fn ($idLegajo) => ! isset($activosPorId[$idLegajo])));
        if ($candidatos === []) {
            return;
        }
        $empresaId = null;
        if ($codigoEmpresa !== null) {
            $empresaId = $this->empresas->porCodigo($codigoEmpresa)?->id;
        }
        $legajosOrigen = $this->legajos->porIds(array_values(array_intersect_key($mapa, array_flip($candidatos))));
        foreach ($this->relaciones->porIds($candidatos) as $rl) {
            if ($rl->estado !== EstadoRelacion::Vigente || ($empresaId !== null && $rl->empresaId !== $empresaId)) {
                continue;
            }
            $origen = $legajosOrigen[$rl->idLegajo] ?? null;
            $egreso = $origen?->fechaEgreso ?? $this->reloj->hoy();
            if ($origen === null) {
                $reporte->avisos[] = "Relación #{$rl->id} (legajo {$rl->legajo}): el registro de origen ya no existe; se da de baja al {$egreso->format('Y-m-d')}.";
            }
            $this->uow->ejecutar(fn () => $this->relaciones->guardar(new RelacionLaboral(
                $rl->id, $rl->idLegajo, $rl->empresaId, $rl->legajo, $rl->fechaIngreso, $egreso, EstadoRelacion::Baja, $rl->cuil, $rl->apellidoNombre, $rl->fechaAntiguedad,
                $rl->categorias, $rl->encuadreArca, $rl->regimenes, $rl->situaciones,
            ), $usuarioId));
            $reporte->bajas++;
        }
    }
}
