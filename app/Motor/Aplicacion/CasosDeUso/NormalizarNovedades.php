<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\NovedadOrigen;
use App\Motor\Aplicacion\DTO\ReporteNormalizacion;
use App\Motor\Aplicacion\Puertos\ProveedorLegajos;
use App\Motor\Aplicacion\Puertos\ProveedorNovedadesOrigen;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioIfNovedadOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioIfRelacionOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioNovedades;
use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use App\Motor\Dominio\Entidades\IfNovedadOrigen;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\Enums\EstadoIfNovedad;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Traduce las novedades legacy del período (sue028s × columna) a novedades
 * canónicas usando motor_novedad_mapeo. Idempotente por IF (clave id:columna) +
 * hash. Lo que no puede normalizarse queda registrado en la IF con su estado
 * (SIN_MAPEO / SIN_RELACION_LABORAL / IGNORADA) para el panel de integridad.
 */
final class NormalizarNovedades
{
    public const SISTEMA = 'ZONDA_GESTION';

    public const TABLA = 'sue028s';

    public function __construct(
        private readonly ProveedorNovedadesOrigen $origen,
        private readonly ProveedorLegajos $legajos,
        private readonly RepositorioTiposNovedad $tipos,
        private readonly RepositorioIfRelacionOrigen $ifRelaciones,
        private readonly RepositorioNovedades $novedades,
        private readonly RepositorioIfNovedadOrigen $ifNovedades,
        private readonly UnidadDeTrabajo $uow,
        private readonly Reloj $reloj,
    ) {}

    public function ejecutar(Periodo $periodo, ?int $usuarioId = null): ReporteNormalizacion
    {
        $reporte = new ReporteNormalizacion;
        $filas = $this->origen->delPeriodo($periodo);
        $mapeos = $this->tipos->mapeos(true);
        $ahora = $this->reloj->ahora();

        // legajo (número) → relación laboral: sue028s.legajo es sue001s.codigo, la IF guarda sue001s.id
        $codigos = array_values(array_unique(array_map(fn (NovedadOrigen $n) => $n->legajoCodigo, $filas)));
        $legajosPorCodigo = $this->legajos->porCodigos($codigos);
        $rlPorIdLegajo = array_flip($this->ifRelaciones->mapaRelacionPorLegajo(SincronizarRelacionesLaborales::SISTEMA, SincronizarRelacionesLaborales::TABLA));

        $clavesVistas = [];
        foreach (array_chunk($filas, 500) as $lote) {
            $this->uow->ejecutar(function () use ($lote, $mapeos, $legajosPorCodigo, $rlPorIdLegajo, $periodo, $ahora, $usuarioId, $reporte, &$clavesVistas) {
                $ifs = $this->ifNovedades->porClaves(self::SISTEMA, self::TABLA, array_map(fn ($n) => $n->claveOrigen(), $lote));
                foreach ($lote as $n) {
                    $clave = $n->claveOrigen();
                    $clavesVistas[$clave] = true;
                    $if = $ifs[$clave] ?? null;
                    if ($if !== null && $if->hashOrigen === $n->hash() && $if->estado === EstadoIfNovedad::Normalizada) {
                        $reporte->sinCambio++;

                        continue;
                    }
                    $mapeo = $mapeos[$n->codNov.'|'.$n->columna] ?? null;
                    if ($mapeo === null) {
                        $reporte->sinMapeo[$n->codNov] = ($reporte->sinMapeo[$n->codNov] ?? 0) + 1;
                        $this->registrar($if, null, $n, $ahora, EstadoIfNovedad::SinMapeo, "[{$periodo}] cod_nov '{$n->codNov}' columna '{$n->columna}' sin mapeo en motor_novedad_mapeo");

                        continue;
                    }
                    if ($mapeo->ignora()) {
                        $reporte->ignoradas++;
                        $this->registrar($if, null, $n, $ahora, EstadoIfNovedad::Ignorada, "[{$periodo}] mapeo con acción IGNORAR");

                        continue;
                    }
                    $legajo = $legajosPorCodigo[$n->legajoCodigo] ?? null;
                    $rlId = $legajo === null ? null : ($rlPorIdLegajo[$legajo->id] ?? null);
                    if ($rlId === null) {
                        $reporte->sinRelacion[$n->legajoCodigo] = ($reporte->sinRelacion[$n->legajoCodigo] ?? 0) + 1;
                        $this->registrar($if, null, $n, $ahora, EstadoIfNovedad::SinRelacionLaboral, "[{$periodo}] legajo {$n->legajoCodigo} sin relación laboral sincronizada");

                        continue;
                    }
                    $valor = BigDecimal::of($n->cantidad)->multipliedBy($mapeo->factor)->toScale(6, RoundingMode::HALF_UP);
                    $novedad = new Novedad($if?->idNovedad, $periodo, $rlId, $mapeo->codigoTipoNovedad,
                        $mapeo->esImporte ? null : $valor->toScale(4, RoundingMode::HALF_UP), $mapeo->esImporte ? Importe::de($valor) : null, self::SISTEMA);
                    $novedad = $this->novedades->guardar($novedad, $usuarioId);
                    $this->registrar($if, $novedad->id, $n, $ahora, EstadoIfNovedad::Normalizada, "[{$periodo}] {$n->codNov}:{$n->columna} → {$mapeo->codigoTipoNovedad}");
                    $if === null ? $reporte->creadas++ : $reporte->actualizadas++;
                }
            });
        }

        // Anulaciones: IFs normalizadas del período cuya fila de origen desapareció
        foreach ($this->ifNovedades->delPeriodo($periodo, self::SISTEMA, self::TABLA) as $if) {
            if ($if->estado === EstadoIfNovedad::Normalizada && ! isset($clavesVistas[$if->claveOrigen])) {
                $this->uow->ejecutar(function () use ($if, $ahora, $periodo) {
                    // Primero se desvincula la interfaz y recién después se borra la novedad:
                    // la FK motor_if_novedad_origen.id_novedad es RESTRICT, el orden inverso falla.
                    $this->ifNovedades->guardar(new IfNovedadOrigen($if->id, null, $if->sistemaOrigen, $if->tablaOrigen, $if->claveOrigen, $ahora, $if->hashOrigen, EstadoIfNovedad::Anulada, "[{$periodo}] la fila de origen ya no existe"));
                    if ($if->idNovedad !== null) {
                        $this->novedades->eliminar($if->idNovedad);
                    }
                });
                $reporte->anuladas++;
            }
        }

        return $reporte;
    }

    private function registrar(?IfNovedadOrigen $if, ?int $idNovedad, NovedadOrigen $n, \DateTimeImmutable $ahora, EstadoIfNovedad $estado, string $motivo): void
    {
        $this->ifNovedades->guardar(new IfNovedadOrigen($if?->id, $idNovedad, self::SISTEMA, self::TABLA, $n->claveOrigen(), $ahora, $n->hash(), $estado, $motivo));
    }
}
