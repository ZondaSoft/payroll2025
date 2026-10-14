<?php

namespace App\Motor\Dominio\Formulas;

use Brick\Math\BigDecimal;

/**
 * Una base de Zonda armada con los conceptos ya calculados. Port de construirBase().
 *
 * Suma los resultados firmados de sus integrantes, omite los que no aplican e
 * incluye los que dieron cero. Si un integrante está BLOQUEADO, la base se bloquea
 * y con ella todo lo que la consuma: no se suma «lo que haya».
 */
final readonly class BaseConstruida
{
    public const OK = 'OK';

    public const BLOQUEADA = 'BLOQUEADA';

    /**
     * @param  array<string, string>  $incluidos  concepto => importe
     * @param  array<string, string>  $excluidos  concepto => motivo
     */
    private function __construct(
        public string $codigoBase,
        public string $estado,
        public ?BigDecimal $importe = null,
        public array $incluidos = [],
        public array $excluidos = [],
        public ?string $motivo = null,
    ) {}

    /**
     * @param  array<string, ResultadoConcepto>  $calculados
     * @param  list<string>  $integrantes
     */
    public static function armar(string $codigoBase, array $integrantes, array $calculados): self
    {
        $importe = BigDecimal::zero();
        $incluidos = [];
        $excluidos = [];

        foreach ($integrantes as $codigo) {
            $r = $calculados[$codigo] ?? null;
            if ($r === null) {
                $excluidos[$codigo] = 'sin calcular';

                continue;
            }
            if ($r->estado === ResultadoConcepto::NO_APLICA) {
                $excluidos[$codigo] = 'no aplica';

                continue;
            }
            if ($r->estado === ResultadoConcepto::BLOQUEADO || $r->estado === ResultadoConcepto::NO_MODELADO) {
                return new self($codigoBase, self::BLOQUEADA, null, $incluidos, $excluidos,
                    "{$codigo} está ".strtolower($r->estado)." ({$r->codigoError})");
            }
            $importe = $importe->plus($r->importeResultado);
            $incluidos[$codigo] = (string) $r->importeResultado;
        }

        // Sumar importes de distinta escala acumula ceros a la derecha que no
        // significan precisión: el valor es el mismo y así la traza es legible.
        return new self($codigoBase, self::OK, $importe->stripTrailingZeros(), $incluidos, $excluidos);
    }

    /** @param list<string> $nodos */
    public static function enCiclo(string $codigoBase, array $nodos): self
    {
        return new self($codigoBase, self::BLOQUEADA,
            motivo: 'La base participa de un ciclo de dependencias: '.implode(', ', $nodos));
    }

    public static function inexistente(string $codigoBase): self
    {
        return new self($codigoBase, self::BLOQUEADA, motivo: 'la base no existe en el catálogo');
    }

    public function esOk(): bool
    {
        return $this->estado === self::OK;
    }
}
