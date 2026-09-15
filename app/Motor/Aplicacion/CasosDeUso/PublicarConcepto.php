<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Aplicacion\Puertos\UnidadDeTrabajo;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\Enums\TipoCambio;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Servicios\SnapshotConcepto;
use App\Motor\Dominio\Servicios\ValidadorConcepto;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use DateTimeImmutable;

/**
 * Publica una versión del concepto (7 casos obligatorios del mockup):
 *  1. la edición en borrador no toca versiones (se liquida siempre sobre snapshots)
 *  2. publicación futura: v2 desde una fecha posterior no afecta períodos anteriores
 *  3. versión intermedia: recorta la anterior y la nueva se cierra el día antes de la siguiente
 *  4. baja futura: vigencia_hasta en la versión vigente
 *  - una versión USADA en liquidación jamás se modifica (RT-006): si se intenta
 *    reemplazar con la misma fecha, se bloquea.
 *  - si el snapshot no cambió (mismo hash) no se crea versión.
 * Corre bajo UnidadDeTrabajo con bloqueo pesimista del concepto.
 */
final class PublicarConcepto
{
    public function __construct(
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioVersiones $versiones,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly RepositorioBasesZonda $basesZonda,
        private readonly RepositorioAplicabilidad $aplicabilidad,
        private readonly UnidadDeTrabajo $uow,
        private readonly Reloj $reloj,
        private readonly SnapshotConcepto $snapshot = new SnapshotConcepto,
        private readonly ValidadorConcepto $validador = new ValidadorConcepto,
    ) {}

    /**
     * @return array{version: ?ConceptoVersion, sin_cambios: bool, validaciones: list<array<string, mixed>>}
     *
     * @throws MotorBloqueado si hay errores de validación o se intenta modificar una versión usada
     */
    public function ejecutar(int $idConcepto, DateTimeImmutable $vigenciaDesde, ?string $motivo = null, ?TipoCambio $tipoCambio = null, ?int $usuarioId = null, ?DateTimeImmutable $vigenciaHasta = null, bool $forzar = false): array
    {
        return $this->uow->ejecutar(function () use ($idConcepto, $vigenciaDesde, $motivo, $tipoCambio, $usuarioId, $vigenciaHasta, $forzar) {
            $this->conceptos->bloquearParaPublicar($idConcepto);
            $c = $this->conceptos->porId($idConcepto) ?? throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, "Concepto #{$idConcepto} inexistente.", 'motor_conceptos');
            $codigo = (string) $c->codigo;

            $incid = $this->incidencias->deConcepto($codigo);
            $bz = $this->basesZonda->incidenciasDeConcepto($codigo);
            $reglas = $this->aplicabilidad->deConcepto($codigo);

            $validaciones = $forzar ? [] : $this->validador->validar($c, $incid, $bz);
            if ($this->validador->tieneErrores($validaciones)) {
                throw new MotorBloqueado(CodigoBloqueo::ConfiguracionInvalida, "El concepto {$codigo} tiene errores de validación y no puede publicarse: ".implode(' | ', array_map(fn ($v) => $v['mensaje'], array_filter($validaciones, fn ($v) => $v['nivel'] === 'ERROR'))), 'motor_conceptos');
            }

            $snapshot = $this->snapshot->construir($c, $incid, $bz, $reglas);
            $hash = $this->snapshot->hash($snapshot);
            $existentes = $this->versiones->deConcepto($idConcepto);

            // misma fecha de inicio que una versión existente
            foreach ($existentes as $v) {
                if ($v->vigencia->desde->format('Y-m-d') === $vigenciaDesde->format('Y-m-d')) {
                    if ($v->usadaEnLiquidacion) {
                        throw MotorBloqueado::porque(CodigoBloqueo::ConfiguracionInvalida, 'No se puede reemplazar: la versión ya fue utilizada en una liquidación cerrada. Corresponde una nueva versión, un ajuste o una reliquidación formal.', 'motor_concepto_versiones', 'usada_en_liquidacion');
                    }
                    if ($v->hash === $hash && $v->vigencia->hasta?->format('Y-m-d') === $vigenciaHasta?->format('Y-m-d')) {
                        return ['version' => $v, 'sin_cambios' => true, 'validaciones' => $validaciones];
                    }
                    // reemplazo de una versión no usada con la misma fecha (caso 4: baja futura sobre la vigente)
                    $nueva = $this->versiones->guardar(new ConceptoVersion($v->id, $idConcepto, $codigo, $v->numeroVersion, new Vigencia($vigenciaDesde, $vigenciaHasta ?? $v->vigencia->hasta), $snapshot, $hash,
                        SnapshotConcepto::ESQUEMA, $motivo ?? $v->motivo, $tipoCambio ?? $v->tipoCambio, false, $this->reloj->ahora(), $usuarioId));
                    $this->conceptos->cambiarEstadoConfiguracion($idConcepto, EstadoConfiguracion::Publicado->value);

                    return ['version' => $nueva, 'sin_cambios' => false, 'validaciones' => $validaciones];
                }
            }

            // vigente a la fecha de inicio nueva: si tiene el mismo hash, no hay nada que publicar
            $vigente = null;
            foreach ($existentes as $v) {
                if ($v->vigencia->contiene($vigenciaDesde)) {
                    $vigente = $v;
                }
            }
            if ($vigente !== null && $vigente->hash === $hash && $vigenciaHasta === null) {
                return ['version' => $vigente, 'sin_cambios' => true, 'validaciones' => $validaciones];
            }

            // cerrar la vigente al día anterior (caso 2 y 3: recorte)
            if ($vigente !== null && $vigente->vigencia->desde->format('Y-m-d') < $vigenciaDesde->format('Y-m-d')) {
                $this->versiones->cerrarVigencia($vigente->id, $vigenciaDesde->modify('-1 day'));
            }
            // la nueva termina el día antes de la siguiente versión ya existente (caso 3: versión intermedia)
            $hasta = $vigenciaHasta;
            foreach ($existentes as $v) {
                if ($v->vigencia->desde->format('Y-m-d') > $vigenciaDesde->format('Y-m-d') && ($hasta === null || $v->vigencia->desde->modify('-1 day') < $hasta)) {
                    $hasta = $v->vigencia->desde->modify('-1 day');
                }
            }
            $numero = $existentes === [] ? 1 : max(array_map(fn ($v) => $v->numeroVersion, $existentes)) + 1;
            $nueva = $this->versiones->guardar(new ConceptoVersion(null, $idConcepto, $codigo, $numero, new Vigencia($vigenciaDesde, $hasta), $snapshot, $hash,
                SnapshotConcepto::ESQUEMA, $motivo, $tipoCambio, false, $this->reloj->ahora(), $usuarioId));
            $this->conceptos->cambiarEstadoConfiguracion($idConcepto, EstadoConfiguracion::Publicado->value);

            return ['version' => $nueva, 'sin_cambios' => false, 'validaciones' => $validaciones];
        });
    }
}
