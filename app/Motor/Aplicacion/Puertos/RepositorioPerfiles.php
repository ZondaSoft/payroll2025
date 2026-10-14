<?php

namespace App\Motor\Aplicacion\Puertos;

use App\Motor\Dominio\Entidades\Perfil;
use App\Motor\Dominio\Entidades\PerfilConcepto;
use DateTimeImmutable;

/**
 * Perfiles de liquidación: qué conceptos corresponden a un ámbito y con qué
 * variante.
 *
 * La regla ganadora aporta las dos cosas juntas, y por eso las reglas viajan
 * siempre con su perfil: `aplica` sin variante no alcanza para elegir fórmula.
 */
interface RepositorioPerfiles
{
    /** @return list<Perfil> perfiles activos y vigentes, con sus reglas de concepto cargadas */
    public function vigentes(DateTimeImmutable $fecha): array;

    /** @return list<Perfil> todos, vigentes o no, con sus reglas */
    public function todos(): array;

    public function porId(int $id): ?Perfil;

    public function guardar(Perfil $p, ?int $usuarioId = null): Perfil;

    public function eliminar(int $id): void;

    public function guardarRegla(int $idPerfil, PerfilConcepto $regla): PerfilConcepto;

    public function eliminarRegla(int $idPerfil, int $idRegla): void;
}
