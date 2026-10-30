<?php

namespace App\Http\Controllers;

use App\Models\SueConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Parámetros generales del sistema (/config): el registro único de sue_config.
 * No hay alta, baja, búsqueda ni navegación entre registros; solo ver y editar.
 */
class ConfigGeneralController extends Controller
{
    private const ACTIVE = 81;

    public function index()
    {
        return $this->render(false);
    }

    public function edit()
    {
        return $this->render(true);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'lsd_no_conceptos' => 'required|boolean',
        ], [
            'lsd_no_conceptos.required' => 'Indique si el LSD se genera sin conceptos.',
            'lsd_no_conceptos.boolean' => 'El valor de "LSD sin conceptos" no es válido.',
        ]);

        SueConfig::actual()->update($validated + ['usuario_id' => $request->user()?->id]);

        return redirect()->route('config.general.index')
            ->with('success', 'Parámetros generales actualizados exitosamente.');
    }

    private function render(bool $edicion)
    {
        return Inertia::render('Config/Index', [
            'config' => SueConfig::actual()->load('usuario:id,name'),
            'edicion' => $edicion,
            'active' => self::ACTIVE,
        ]);
    }
}
