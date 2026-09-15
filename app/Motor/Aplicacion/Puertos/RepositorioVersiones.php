<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use DateTimeInterface;

/** Versiones publicadas por vigencia. vigentesA() es la única lectura del liquidador. */
interface RepositorioVersiones
{
    /** @return array<string, ConceptoVersion> indexado por código de concepto */
    public function vigentesA(DateTimeInterface $fecha): array;

    /** @return list<ConceptoVersion> ordenadas por vigencia_desde */
    public function deConcepto(int $idConcepto): array;

    public function porId(int $idVersion): ?ConceptoVersion;

    public function guardar(ConceptoVersion $version): ConceptoVersion;

    public function cerrarVigencia(int $idVersion, DateTimeInterface $hasta): void;

    /** @param list<int> $idsVersion */
    public function marcarUsadas(array $idsVersion): void;

    /** @return list<int> ids de conceptos PUBLICADOS sin ninguna versión */
    public function conceptosSinVersion(): array;
}
