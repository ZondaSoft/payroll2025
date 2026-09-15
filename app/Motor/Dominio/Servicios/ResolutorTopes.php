<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\BaseArca;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Enums\CodigoBloqueo;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use App\Motor\Dominio\Liquidador\Resultado\Aviso;
use App\Motor\Dominio\ValorObjetos\Importe;

/**
 * Reglas A-H de TOPES_MINIMOS (spec): "EL MOTOR NO DEBE SUPONER NI ARRASTRAR UN
 * TOPE QUE NO ESTÉ EXPRESAMENTE PARAMETRIZADO".
 *  A base no topeable → sin buscar tope, sin error
 *  B base topeable sin registro → BLOQUEAR (no usar período anterior)
 *  C importe vacío → BLOQUEAR (no es cero)
 *  D SIN_DATO → BLOQUEAR
 *  E SIN_CONFIRMAR → BLOQUEAR en PRODUCCION, aviso en PRUEBA
 *  F duplicidad → BLOQUEAR
 *  G/H sin arrastre temporal ni entre componentes (los topes vienen ya filtrados por período y se buscan por componente exacto)
 */
final class ResolutorTopes
{
    /** @param list<Tope> $topesDelPeriodo */
    public function __construct(private readonly array $topesDelPeriodo, private readonly GateValidacion $gate) {}

    /**
     * Aplica MAXIMO y MINIMO (si existen) a la base. Devuelve [importe topeado, tope máximo aplicado?, tope mínimo aplicado?, avisos].
     *
     * @return array{importe: Importe, maximo: ?Tope, minimo: ?Tope, avisos: list<Aviso>}
     */
    public function aplicar(BaseArca $base, Importe $importe, ComponenteTope $componente): array
    {
        if (! $base->admiteTope) {
            return ['importe' => $importe, 'maximo' => null, 'minimo' => null, 'avisos' => []]; // regla A
        }
        $avisos = [];
        $maximo = $this->buscar($base->codigo, TipoTope::Maximo, $componente, $avisos, obligatorio: true);
        $minimo = $this->buscar($base->codigo, TipoTope::Minimo, $componente, $avisos, obligatorio: false);

        $topeado = $importe;
        if ($maximo !== null && $topeado->mayorQue($maximo->importe)) {
            $topeado = $maximo->importe;
        }
        if ($minimo !== null && $topeado->esPositivo() && $topeado->menorQue($minimo->importe)) {
            $topeado = $minimo->importe;
        }

        return ['importe' => $topeado, 'maximo' => $maximo, 'minimo' => $minimo, 'avisos' => $avisos];
    }

    /** @param list<Aviso> $avisos */
    private function buscar(string $codigoBase, TipoTope $tipo, ComponenteTope $componente, array &$avisos, bool $obligatorio): ?Tope
    {
        $coincidentes = array_values(array_filter($this->topesDelPeriodo, fn (Tope $t) => $t->codigoBase === $codigoBase && $t->tipoTope === $tipo && $t->componente === $componente));
        $clave = "{$codigoBase}/{$tipo->value}/{$componente->value}";
        if (count($coincidentes) > 1) {
            throw MotorBloqueado::porque(CodigoBloqueo::TopeDuplicado, "Más de un tope para {$clave}: configuración ambigua, el motor bloquea (regla F).", 'motor_topes_minimos');
        }
        if ($coincidentes === []) {
            if ($obligatorio) {
                throw MotorBloqueado::porque(CodigoBloqueo::TopeSinParametrizacion, "Falta parametrización de tope para {$clave} en el período (regla B: no se arrastra el período anterior).", 'motor_topes_minimos');
            }

            return null; // el mínimo puede no estar definido (la spec deja REM_4/REM_5 mínimos pendientes)
        }
        $tope = $coincidentes[0];
        if ($tope->importe === null) {
            if ($obligatorio) {
                throw MotorBloqueado::porque(CodigoBloqueo::TopeImporteVacio, "El tope {$tope->codigo} ({$clave}) tiene importe vacío: no se interpreta como cero ni como ausencia (regla C).", 'motor_topes_minimos', 'importe');
            }
            // mínimo pendiente de definición (spec: "los mínimos de REM_4 y REM_5 no deben aplicarse hasta cerrarlos")
            $avisos[] = new Aviso('TOPE_MINIMO_SIN_DATO', "Tope {$tope->codigo} ({$clave}) sin importe: el mínimo no se aplica hasta definirlo.", "motor_topes_minimos:{$tope->codigo}");

            return null;
        }
        $aviso = $this->gate->evaluar($tope->estadoValidacion, "motor_topes_minimos:{$tope->codigo}", "Tope {$tope->codigo} ({$clave})", $obligatorio);
        if ($aviso !== null) {
            $avisos[] = $aviso;
        }

        return $tope;
    }
}
