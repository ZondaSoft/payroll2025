<?php

namespace App\Motor\Aplicacion\DTO;

use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\ExigenciaConcepto;

/**
 * Configuración inmutable del módulo, construida una sola vez por el
 * MotorServiceProvider (único lugar que lee config()).
 */
final readonly class ConfiguracionMotor
{
    /** @var array<int, array{codigo: string, exigencia: ExigenciaConcepto, descripcion: string}> */
    public array $conceptosMinimosSistema;

    /** @param array<int, array{codigo: string, exigencia: string, descripcion?: string}> $conceptosMinimosSistema */
    public function __construct(
        public Entorno $entorno,
        array $conceptosMinimosSistema = [],
    ) {
        $this->conceptosMinimosSistema = array_values(array_map(
            static fn (array $c) => [
                'codigo' => strtoupper(trim((string) $c['codigo'])),
                'exigencia' => ExigenciaConcepto::from(strtoupper((string) ($c['exigencia'] ?? 'OBLIGATORIO'))),
                'descripcion' => (string) ($c['descripcion'] ?? ''),
            ],
            $conceptosMinimosSistema,
        ));
    }
}
