<?php

namespace App\Motor\Infraestructura\Legacy;

use App\Motor\Aplicacion\Puertos\ProveedorIncidenciasLegacy;
use Illuminate\Support\Facades\DB;

/**
 * conceptosarcas → marcas por código AFIP. Las 15 columnas de marcas de la tabla
 * legacy se traducen a las 13 aplicaciones oficiales (subsistema|tipo). Las
 * columnas RENATEA del legacy se mapean al subsistema RENATRE (el vigente).
 * aportes_diferenciales/aportes_especiales (REM_6/REM_7) no tienen aplicación
 * oficial en SUBSISTEMA_APLICACION y se omiten.
 */
final class AdaptadorIncidenciasConceptosArca implements ProveedorIncidenciasLegacy
{
    public const MARCAS = [
        'aportes_sipa' => 'SIPA|APORTE', 'contribuciones_sipa' => 'SIPA|CONTRIBUCION',
        'aportes_inssjyp' => 'INSSJP|APORTE', 'contribuciones_inssjyp' => 'INSSJP|CONTRIBUCION',
        'aportes_obra_social' => 'OS|APORTE', 'contribuciones_obra_social' => 'OS|CONTRIBUCION',
        'aportes_fsr' => 'FSR|APORTE', 'contribuciones_fsr' => 'FSR|CONTRIBUCION',
        'aportes_renatea' => 'RENATRE|APORTE', 'contribuciones_renatea' => 'RENATRE|CONTRIBUCION',
        'contribuciones_aaff' => 'AAFF|CONTRIBUCION', 'contribuciones_fne' => 'FNE|CONTRIBUCION',
        'contribuciones_lrt' => 'LRT|CONTRIBUCION',
    ];

    public function marcasPorCodigoAfip(?int $idEmpresa = null): array
    {
        $q = DB::table('conceptosarcas')->select(array_merge(['codigo_afip'], array_keys(self::MARCAS)))->whereNotNull('codigo_afip');
        if ($idEmpresa !== null) {
            $q->where('id_empresa', $idEmpresa);
        }
        $out = [];
        foreach ($q->orderBy('codigo_afip')->get() as $f) {
            $codigo = (int) $f->codigo_afip;
            foreach (self::MARCAS as $columna => $aplicacion) {
                $marca = $f->{$columna} !== null && (float) $f->{$columna} > 0;
                $out[$codigo][$aplicacion] = ($out[$codigo][$aplicacion] ?? false) || $marca;
            }
        }

        return $out;
    }
}
