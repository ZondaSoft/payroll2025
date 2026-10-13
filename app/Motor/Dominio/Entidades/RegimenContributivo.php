<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Dominio\Enums\TipoRegla;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use App\Motor\Dominio\ValorObjetos\Vigencia;

final readonly class RegimenContributivo
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $descripcion,
        public TipoRegla $tipoRegla,
        public SujetoObligado $sujetoObligado,
        public ?Porcentaje $porcentaje,
        public ?string $destino,
        /** Subsistema que el régimen reemplaza por completo (tipo_regla SUSTITUCION_SUBSISTEMA). */
        public ?string $reemplazaSubsistema,
        public string $baseAplicable,
        public ?int $duracionMeses,
        public int $ordenAplicacion,
        public Vigencia $vigencia,
        public EstadoValidacion $estadoValidacion,
    ) {}
}
