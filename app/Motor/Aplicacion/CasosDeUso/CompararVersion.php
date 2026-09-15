<?php

namespace App\Motor\Aplicacion\CasosDeUso;

use App\Motor\Aplicacion\DTO\DiffVersion;
use App\Motor\Aplicacion\Puertos\Reloj;
use App\Motor\Aplicacion\Puertos\RepositorioAplicabilidad;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Servicios\SnapshotConcepto;

/** Diff campo a campo entre el borrador (fila viva) y la versión vigente hoy, o entre dos versiones. */
final class CompararVersion
{
    public function __construct(
        private readonly RepositorioConceptos $conceptos,
        private readonly RepositorioVersiones $versiones,
        private readonly RepositorioIncidenciasArca $incidencias,
        private readonly RepositorioBasesZonda $basesZonda,
        private readonly RepositorioAplicabilidad $aplicabilidad,
        private readonly Reloj $reloj,
        private readonly SnapshotConcepto $snapshot = new SnapshotConcepto,
    ) {}

    public function borradorContraVigente(int $idConcepto): DiffVersion
    {
        $c = $this->conceptos->porId($idConcepto);
        if ($c === null) {
            return new DiffVersion('', null, null, [], true);
        }
        $codigo = (string) $c->codigo;
        $actual = $this->snapshot->construir($c, $this->incidencias->deConcepto($codigo), $this->basesZonda->incidenciasDeConcepto($codigo), $this->aplicabilidad->deConcepto($codigo));
        $vigente = null;
        foreach ($this->versiones->deConcepto($idConcepto) as $v) {
            if ($v->vigencia->contiene($this->reloj->hoy())) {
                $vigente = $v;
            }
        }
        if ($vigente === null) {
            return new DiffVersion($codigo, null, null, [['ruta' => '*', 'antes' => null, 'despues' => 'sin versión vigente: todo el borrador es nuevo']], false);
        }
        $cambios = $this->snapshot->diferencias($vigente->snapshot, $actual);

        return new DiffVersion($codigo, $vigente->numeroVersion, null, $cambios, $cambios === []);
    }

    public function entreVersiones(int $idVersionA, int $idVersionB): DiffVersion
    {
        $a = $this->versiones->porId($idVersionA);
        $b = $this->versiones->porId($idVersionB);
        if ($a === null || $b === null) {
            return new DiffVersion('', null, null, [], true);
        }
        $cambios = $this->snapshot->diferencias($a->snapshot, $b->snapshot);

        return new DiffVersion($a->codigoConcepto, $a->numeroVersion, $b->numeroVersion, $cambios, $cambios === []);
    }
}
