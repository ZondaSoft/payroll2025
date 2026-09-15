<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\ImportarCatalogosSicoss;
use App\Motor\Aplicacion\CasosDeUso\ImportarIncidenciasDesdeConceptosArca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Acciones de importación desde el legacy (botones): catálogos SICOSS e incidencias desde conceptosarcas. */
class ImportacionesController extends Controller
{
    public function catalogosSicoss(ImportarCatalogosSicoss $caso): JsonResponse
    {
        return response()->json(['success' => true, 'insertadas' => $caso->ejecutar()]);
    }

    public function incidenciasArca(Request $request, ImportarIncidenciasDesdeConceptosArca $caso): JsonResponse
    {
        $datos = $request->validate(['id_empresa' => 'nullable|integer|exists:sue086s,id']);

        return response()->json(['success' => true] + $caso->ejecutar($datos['id_empresa'] ?? null, auth()->id()));
    }
}
