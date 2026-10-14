<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\Perfil;
use App\Motor\Dominio\Entidades\PerfilConcepto;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Formulas\DecisionPerfil;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;

/**
 * Decide, concepto por concepto, qué dice el perfil del trabajador.
 *
 * Recorre los perfiles que alcanzan al trabajador y se queda con la regla del
 * nivel más específico que se pronuncie sobre el concepto: TRABAJADOR gana sobre
 * EMPRESA, que gana sobre CATEGORIA, CONVENIO y GENERAL. Un nivel que no declara
 * el concepto no vota — la búsqueda sigue hacia los niveles más generales.
 *
 * Se diferencia de ResolutorAplicabilidad en tres cosas, y las tres son del
 * contrato: la regla ganadora aporta la variante además del `aplica`; dos reglas
 * contradictorias del mismo nivel dan PERFIL_AMBIGUO en vez de un conflicto
 * fatal que corta la liquidación entera; y que ningún perfil se pronuncie da
 * PERFIL_SIN_REGLA en vez de omitir el concepto en silencio.
 */
final class ResolutorPerfiles
{
    public const AMBIGUO = 'PERFIL_AMBIGUO';

    public const SIN_REGLA = 'PERFIL_SIN_REGLA';

    public const SIN_VARIANTE = 'ERROR_CONFIGURACION';

    /**
     * @param  list<Perfil>  $perfiles  todos los del catálogo; acá se filtran los que alcanzan al trabajador
     * @param  list<string>  $conceptos  universo a decidir
     * @return array<string, DecisionPerfil>
     */
    public function decidir(array $perfiles, array $conceptos, ContextoRelacionLaboral $ctx): array
    {
        $alcanzan = $this->aplicables($perfiles, $ctx);
        $decisiones = [];
        foreach ($conceptos as $codigo) {
            $decisiones[$codigo] = $this->decidirConcepto($codigo, $alcanzan, $ctx);
        }

        return $decisiones;
    }

    /**
     * @param  list<Perfil>  $perfiles
     * @return list<Perfil> ordenados del más específico al más general
     */
    public function aplicables(array $perfiles, ContextoRelacionLaboral $ctx): array
    {
        $alcanzan = array_values(array_filter(
            $perfiles,
            fn (Perfil $p) => $p->activo && $p->vigencia->contiene($ctx->fechaResolucion) && $this->coincide($p, $ctx)
        ));
        usort($alcanzan, fn (Perfil $a, Perfil $b) => [$a->precedencia(), $a->codigo] <=> [$b->precedencia(), $b->codigo]);

        return $alcanzan;
    }

    /** @param list<Perfil> $alcanzan ya ordenados por precedencia */
    private function decidirConcepto(string $codigo, array $alcanzan, ContextoRelacionLaboral $ctx): DecisionPerfil
    {
        // Agrupar por nivel para poder detectar la contradicción dentro del nivel
        // antes de dejar que el nivel siguiente opine.
        /** @var array<int, list<array{0: Perfil, 1: PerfilConcepto}>> $porNivel */
        $porNivel = [];
        foreach ($alcanzan as $perfil) {
            $regla = $perfil->concepto($codigo);
            if ($regla === null || ! $regla->vigencia->contiene($ctx->fechaResolucion)) {
                continue;
            }
            $porNivel[$perfil->precedencia()][] = [$perfil, $regla];
        }

        if ($porNivel === []) {
            return DecisionPerfil::bloqueada(self::SIN_REGLA,
                "Ningún perfil del trabajador se pronuncia sobre {$codigo}: la configuración está incompleta");
        }

        ksort($porNivel);
        $nivel = array_key_first($porNivel);
        $candidatas = $porNivel[$nivel];

        if (count($candidatas) > 1 && ! $this->coinciden($candidatas)) {
            $perfilesEnConflicto = implode(', ', array_map(fn (array $c) => $c[0]->codigo, $candidatas));
            $nombreNivel = $candidatas[0][0]->nivel->value;

            return DecisionPerfil::bloqueada(self::AMBIGUO,
                "Dos perfiles de nivel {$nombreNivel} se contradicen sobre {$codigo} ({$perfilesEnConflicto})", $nombreNivel);
        }

        [$perfil, $regla] = $candidatas[0];

        if (! $regla->aplica) {
            return DecisionPerfil::noAplica(
                $regla->motivo ?? "excluido por el perfil {$perfil->codigo}", $perfil->nivel->value, $perfil->codigo
            );
        }

        if (trim((string) $regla->variante) === '') {
            return DecisionPerfil::bloqueada(self::SIN_VARIANTE,
                "El perfil {$perfil->codigo} incluye {$codigo} sin decir con qué variante se liquida", $perfil->nivel->value);
        }

        return DecisionPerfil::aplica($regla->variante, $perfil->nivel->value, $perfil->codigo);
    }

    /** Dos reglas del mismo nivel sólo conviven si dicen exactamente lo mismo. */
    private function coinciden(array $candidatas): bool
    {
        $firmas = array_map(
            fn (array $c) => ($c[1]->aplica ? '1' : '0').'|'.($c[1]->variante ?? ''),
            $candidatas
        );

        return count(array_unique($firmas)) === 1;
    }

    private function coincide(Perfil $p, ContextoRelacionLaboral $ctx): bool
    {
        return match ($p->nivel) {
            TipoAmbito::General => true,
            TipoAmbito::Convenio => $p->idConvenio !== null && $p->idConvenio === $ctx->idConvenio,
            TipoAmbito::Categoria => $p->idCategoria !== null && $p->idCategoria === $ctx->idCategoria,
            TipoAmbito::Empresa => $p->idEmpresa !== null && $p->idEmpresa === $ctx->relacion->empresaId,
            TipoAmbito::Trabajador => $p->relacionLaboralId !== null && (string) $p->relacionLaboralId === (string) $ctx->relacion->id,
        };
    }
}
