<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;

/** Maestro de conceptos (fila viva). El liquidador NO lo usa: usa RepositorioVersiones. */
interface RepositorioConceptos
{
    /** @return list<Concepto> */
    public function todos(bool $soloActivos = false): array;

    public function porCodigo(CodigoConcepto $codigo): ?Concepto;

    public function porId(int $id): ?Concepto;

    /** Inserta o actualiza; devuelve el concepto con id. */
    public function guardar(Concepto $concepto, ?int $usuarioId = null): Concepto;

    /** Bloqueo pesimista (lockForUpdate) para publicar sin versiones solapadas. Solo dentro de UnidadDeTrabajo. */
    public function bloquearParaPublicar(int $idConcepto): void;

    public function cambiarEstadoConfiguracion(int $idConcepto, string $estado): void;

    public function darDeBaja(int $idConcepto): void;
}
