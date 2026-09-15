<?php

namespace App\Motor\Dominio\Liquidador\Resultado;

use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Porcentaje;

/** Contribución patronal calculada aplicando BASE → SUSTITUTIVO → ADICIONAL → DESTINO. */
final readonly class LineaContribucion
{
    public function __construct(
        public string $codigoConcepto,
        public string $codigoSubsistema,
        public string $codigoBase,
        public Importe $base,
        public Porcentaje $porcentaje,
        public Importe $importe,
        public string $reglaAplicada,
        public string $codigoRegimen,
        public ?string $encuadramiento,
        public ?string $destino,
        public bool $tecnico = false,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return [
            'concepto' => $this->codigoConcepto, 'subsistema' => $this->codigoSubsistema, 'base' => $this->codigoBase,
            'base_importe' => $this->base->aTexto(2), 'porcentaje' => $this->porcentaje->porciento(), 'importe' => $this->importe->aTexto(2),
            'regla' => $this->reglaAplicada, 'regimen' => $this->codigoRegimen, 'encuadramiento' => $this->encuadramiento, 'destino' => $this->destino,
            'tecnico' => $this->tecnico,
        ];
    }
}
