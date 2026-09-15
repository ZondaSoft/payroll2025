<?php

namespace App\Motor\Aplicacion\DTO;

final readonly class CatalogoSicossFila
{
    public function __construct(public string $codigo, public string $detalle) {}
}
