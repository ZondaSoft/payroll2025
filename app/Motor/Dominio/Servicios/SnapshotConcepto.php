<?php

namespace App\Motor\Dominio\Servicios;

use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;

/**
 * Construye el snapshot canónico de un concepto y su hash. Es la única fuente
 * del formato: lo usan PublicarConcepto, CompararVersion y el seeder de versiones.
 * Referencias por código natural (no por id) para que sea legible y estable.
 */
final class SnapshotConcepto
{
    public const ESQUEMA = 1;

    /**
     * @param  list<IncidenciaArca>  $incidenciasArca
     * @param  list<IncidenciaBaseZonda>  $basesZonda
     * @param  list<ReglaAplicabilidad>  $aplicabilidad
     * @return array<string, mixed>
     */
    public function construir(Concepto $concepto, array $incidenciasArca, array $basesZonda, array $aplicabilidad): array
    {
        $arca = array_map(fn (IncidenciaArca $i) => $i->aArray() + ['id_subsistema_aplicacion' => $i->idSubsistemaAplicacion], $incidenciasArca);
        usort($arca, fn ($a, $b) => [$a['codigo_subsistema'], $a['tipo_aplicacion'], $a['vigencia']['desde']] <=> [$b['codigo_subsistema'], $b['tipo_aplicacion'], $b['vigencia']['desde']]);

        $bz = array_map(fn (IncidenciaBaseZonda $i) => $i->aArray() + ['id_base_zonda' => $i->idBaseZonda], $basesZonda);
        usort($bz, fn ($a, $b) => [$a['codigo_base_zonda'], $a['vigencia']['desde']] <=> [$b['codigo_base_zonda'], $b['vigencia']['desde']]);

        $ap = array_map(fn (ReglaAplicabilidad $r) => $r->aArray(), $aplicabilidad);
        usort($ap, fn ($a, $b) => [$a['tipo_ambito'], (string) $a['id_convenio'], (string) $a['id_categoria'], (string) $a['id_empresa'], (string) $a['relacion_laboral_id'], $a['vigencia']['desde']]
            <=> [$b['tipo_ambito'], (string) $b['id_convenio'], (string) $b['id_categoria'], (string) $b['id_empresa'], (string) $b['relacion_laboral_id'], $b['vigencia']['desde']]);

        return [
            'esquema' => self::ESQUEMA,
            'concepto' => $concepto->aDefinicion(),
            'incidencias_arca' => array_values($arca),
            'bases_zonda' => array_values($bz),
            'aplicabilidad' => array_values($ap),
        ];
    }

    /** sha256 del JSON canónico (claves ordenadas recursivamente, sin escapes de unicode/slash). */
    public function hash(array $snapshot): string
    {
        return hash('sha256', $this->jsonCanonico($snapshot));
    }

    public function jsonCanonico(array $snapshot): string
    {
        $copia = $snapshot;
        $this->ordenarClaves($copia);

        return (string) json_encode($copia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return list<array{ruta: string, antes: mixed, despues: mixed}> */
    public function diferencias(array $a, array $b, string $ruta = ''): array
    {
        $cambios = [];
        $claves = array_unique(array_merge(array_keys($a), array_keys($b)));
        sort($claves);
        foreach ($claves as $k) {
            $r = $ruta === '' ? (string) $k : $ruta.'.'.$k;
            $va = $a[$k] ?? null;
            $vb = $b[$k] ?? null;
            if (is_array($va) && is_array($vb) && ! array_is_list($va) && ! array_is_list($vb)) {
                $cambios = array_merge($cambios, $this->diferencias($va, $vb, $r));
            } elseif ($va !== $vb) {
                $cambios[] = ['ruta' => $r, 'antes' => $va, 'despues' => $vb];
            }
        }

        return $cambios;
    }

    private function ordenarClaves(array &$arr): void
    {
        if (! array_is_list($arr)) {
            ksort($arr);
        }
        foreach ($arr as &$v) {
            if (is_array($v)) {
                $this->ordenarClaves($v);
            }
        }
    }
}
