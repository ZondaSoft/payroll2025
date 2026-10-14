<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\ValorObjetos\Vigencia;

/**
 * Un perfil de liquidación: el conjunto de conceptos que corresponden a un ámbito
 * (un convenio, una categoría, una empresa, un trabajador) y con qué variante.
 *
 * Reemplaza a motor_concepto_aplicabilidad, que decidía si el concepto aplicaba
 * pero no con qué variante. La variante es lo que distingue el BASICO del 371/03
 * (prorrateado por jornada pactada) del BASICO del 40/89 (de escala, sin prorrateo),
 * y sin ella el motor no puede elegir fórmula.
 *
 * La precedencia es la del contrato: 1 TRABAJADOR, 2 EMPRESA, 3 CATEGORIA,
 * 4 CONVENIO, 5 GENERAL. Gana el número más bajo — el ámbito más específico.
 */
final readonly class Perfil
{
    public const PRECEDENCIAS = [
        'TRABAJADOR' => 1,
        'EMPRESA' => 2,
        'CATEGORIA' => 3,
        'CONVENIO' => 4,
        'GENERAL' => 5,
    ];

    /** @param list<PerfilConcepto> $conceptos */
    public function __construct(
        public ?int $id,
        public string $codigo,
        public string $nombre,
        public TipoAmbito $nivel,
        public Vigencia $vigencia,
        public ?int $relacionLaboralId = null,
        public ?int $idEmpresa = null,
        public ?int $idCategoria = null,
        public ?int $idConvenio = null,
        public bool $activo = true,
        public array $conceptos = [],
    ) {}

    public function precedencia(): int
    {
        return self::PRECEDENCIAS[$this->nivel->value];
    }

    public function concepto(string $codigoConcepto): ?PerfilConcepto
    {
        foreach ($this->conceptos as $c) {
            if ($c->codigoConcepto === $codigoConcepto) {
                return $c;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo_perfil' => $this->codigo,
            'nombre' => $this->nombre,
            'nivel' => $this->nivel->value,
            'orden_precedencia' => $this->precedencia(),
            'relacion_laboral_id' => $this->relacionLaboralId,
            'empresa_id' => $this->idEmpresa,
            'id_categoria' => $this->idCategoria,
            'id_convenio' => $this->idConvenio,
            'activo' => $this->activo,
            'vigencia' => $this->vigencia->jsonSerialize(),
            'conceptos' => count($this->conceptos),
        ];
    }
}
