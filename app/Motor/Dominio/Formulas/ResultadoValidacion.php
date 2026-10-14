<?php

namespace App\Motor\Dominio\Formulas;

/** Errores, advertencias y dependencias de una versión validada. */
final class ResultadoValidacion
{
    /** @var list<array{codigo: string, mensaje: string}> */
    public array $errores = [];

    /** @var list<array{codigo: string, mensaje: string}> */
    public array $advertencias = [];

    /** @var list<array{tipo: string, codigo: string, arista: bool, dependencias?: list<string>}> */
    public array $dependencias = [];

    /** @var list<string> */
    public array $sinUnidad = [];

    /** @var array<string, ResultadoAnalisis> por componente */
    public array $analisis = [];

    public ?Dimension $unidadImporte = null;

    public function __construct(public readonly string $modo = ValidadorVersion::PUBLICACION) {}

    public function error(string $codigo, string $mensaje): void
    {
        $this->errores[] = ['codigo' => $codigo, 'mensaje' => $mensaje];
    }

    public function advertencia(string $codigo, string $mensaje): void
    {
        $this->advertencias[] = ['codigo' => $codigo, 'mensaje' => $mensaje];
    }

    /** Incorpora el análisis de un componente: problemas etiquetados, dependencias y literales. */
    public function registrar(string $componente, ResultadoAnalisis $analisis, VersionFormula $version): void
    {
        $this->analisis[$componente] = $analisis;
        foreach ($analisis->sinUnidad as $x) {
            if (! in_array($x, $this->sinUnidad, true)) {
                $this->sinUnidad[] = $x;
            }
        }
        foreach ($analisis->dependencias as $d) {
            $this->dependencias[] = $d;
        }
        foreach ($analisis->problemas as $p) {
            $this->error($p['codigo'], $this->etiqueta($componente).': '.$p['mensaje']);
        }
        $this->revisarLiterales($analisis, $version);
    }

    /**
     * Un literal distinto de 0 o 1 necesita justificación declarada para publicarse
     * (§15): el 1.5 de las horas extras al 50 % no es un número cualquiera, es el
     * recargo del art. 201 de la LCT.
     */
    private function revisarLiterales(ResultadoAnalisis $analisis, VersionFormula $version): void
    {
        foreach ($analisis->literales as $literal) {
            $texto = $literal['valor'];
            if ($texto === '0' || $texto === '1' || (float) $texto === 0.0 || (float) $texto === 1.0) {
                continue;
            }
            if (isset($version->justificaciones[$texto])) {
                $this->advertencia('DSL_LITERAL_A_JUSTIFICAR', "Literal {$texto} justificado: {$version->justificaciones[$texto]}");
            } else {
                $this->error('DSL_LITERAL_SIN_JUSTIFICAR', "El literal {$texto} necesita una justificación declarada para publicarse");
            }
        }
    }

    private function etiqueta(string $componente): string
    {
        return match ($componente) {
            ContextoAnalisis::CONDICION => 'Condición',
            ContextoAnalisis::CANTIDAD => 'Cantidad',
            ContextoAnalisis::VALOR => 'Valor unitario',
            ContextoAnalisis::IMPORTE => 'Importe',
            default => $componente,
        };
    }

    /** En EDICION los códigos de migración y la unidad sin declarar pasan a advertencia. */
    public function cerrar(): self
    {
        if ($this->modo === ValidadorVersion::EDICION) {
            $bloqueantes = [];
            foreach ($this->errores as $e) {
                if (in_array($e['codigo'], Dsl::CODIGOS_SOLO_PUBLICACION, true)) {
                    $this->advertencias[] = $e;
                } else {
                    $bloqueantes[] = $e;
                }
            }
            $this->errores = $bloqueantes;
        }

        return $this;
    }

    public function publica(): bool
    {
        return $this->errores === [];
    }

    /** @return list<string> */
    public function codigos(): array
    {
        return array_values(array_unique(array_column($this->errores, 'codigo')));
    }

    public function tiene(string $codigo): bool
    {
        return in_array($codigo, $this->codigos(), true)
            || in_array($codigo, array_column($this->advertencias, 'codigo'), true);
    }

    /** @return list<array{tipo: string, codigo: string, dependencias?: list<string>}> */
    public function aristas(): array
    {
        return array_values(array_filter($this->dependencias, fn (array $d) => $d['arista']));
    }
}
