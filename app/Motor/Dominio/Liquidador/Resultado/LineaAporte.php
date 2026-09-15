<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Porcentaje;

/** Aporte personal calculado por el módulo de seguridad social: base × alícuota (sobre base ya topeada). */
final readonly class LineaAporte
{
    public function __construct(
        public string $codigoConcepto,
        public string $codigoSubsistema,
        public string $codigoBase,
        public Importe $base,
        public Porcentaje $porcentaje,
        public Importe $importe,
        public string $codigoAlicuota,
        public ?string $condicionAplicada,
        public bool $tecnico = false,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'concepto' => $this->codigoConcepto, 'subsistema' => $this->codigoSubsistema, 'base' => $this->codigoBase,
            'base_importe' => $this->base->aTexto(2), 'porcentaje' => $this->porcentaje->porciento(), 'importe' => $this->importe->aTexto(2),
            'alicuota' => $this->codigoAlicuota, 'condicion' => $this->condicionAplicada ?? 'TODAS', 'tecnico' => $this->tecnico,
        ];
    }
}
