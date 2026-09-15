<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\ValorObjetos\Importe;
use Brick\Math\BigDecimal;

/** Concepto ejecutado (haber remunerativo/no remunerativo o descuento no contributivo). */
final readonly class LineaHaber
{
    /** @param array<string, mixed> $detalle */
    public function __construct(
        public Concepto $concepto,
        public Importe $importe,
        public Importe $importeExacto,
        public ?BigDecimal $cantidad = null,
        public ?Importe $valorUnitario = null,
        public ?int $idVersion = null,
        public ?int $numeroVersion = null,
        public array $detalle = [],
    ) {}

    public function codigo(): string
    {
        return (string) $this->concepto->codigo;
    }

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'codigo' => $this->codigo(), 'nombre' => $this->concepto->nombre, 'alias' => $this->concepto->aliasRecibo,
            'tipo' => $this->concepto->tipo->value, 'naturaleza' => $this->concepto->naturaleza->value, 'signo' => $this->concepto->signo->value,
            'estrategia' => $this->concepto->estrategia?->value, 'cantidad' => $this->cantidad === null ? null : (string) $this->cantidad,
            'valor_unitario' => $this->valorUnitario?->aTexto(6), 'importe' => $this->importe->aTexto(2), 'importe_exacto' => (string) $this->importeExacto,
            'regla_redondeo' => $this->concepto->reglaRedondeo?->value, 'version' => $this->numeroVersion, 'id_version' => $this->idVersion,
            'detalle' => $this->detalle,
        ];
    }
}
