<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Portada de los 14 catálogos normativos ARCA con conteos y semáforo de estado_validacion. */
class NormativaController extends Controller
{
    public const CATALOGOS = [
        ['ruta' => 'motor.normativa.bases-arca', 'tabla' => 'motor_bases_arca', 'titulo' => 'Bases imponibles ARCA', 'icono' => 'ri-stack-line', 'cerrado' => true],
        ['ruta' => 'motor.normativa.subsistemas', 'tabla' => 'motor_subsistemas', 'titulo' => 'Subsistemas', 'icono' => 'ri-government-line', 'cerrado' => true],
        ['ruta' => 'motor.normativa.aplicaciones', 'tabla' => 'motor_subsistema_aplicacion', 'titulo' => 'Aplicaciones (columnas LSD)', 'icono' => 'ri-layout-column-line', 'cerrado' => true],
        ['ruta' => 'motor.normativa.encuadramientos-patronales', 'tabla' => 'motor_encuadramientos_patronales', 'titulo' => 'Encuadramientos patronales', 'icono' => 'ri-building-line', 'cerrado' => true],
        ['ruta' => 'motor.normativa.alicuotas-contribuciones', 'tabla' => 'motor_alicuotas_contribuciones', 'titulo' => 'Alícuotas de contribuciones', 'icono' => 'ri-percent-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.alicuotas-aportes', 'tabla' => 'motor_alicuotas_aportes', 'titulo' => 'Alícuotas de aportes', 'icono' => 'ri-percent-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.topes', 'tabla' => 'motor_topes_minimos', 'titulo' => 'Topes mínimos y máximos', 'icono' => 'ri-arrow-up-down-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.regimenes', 'tabla' => 'motor_regimenes_contributivos', 'titulo' => 'Regímenes contributivos', 'icono' => 'ri-git-branch-line', 'cerrado' => true],
        ['ruta' => 'motor.normativa.condiciones', 'tabla' => 'motor_condiciones_arca', 'titulo' => 'Condiciones ARCA', 'icono' => 'ri-user-settings-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.modalidades', 'tabla' => 'motor_modalidades_arca', 'titulo' => 'Modalidades ARCA', 'icono' => 'ri-file-list-3-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.situaciones', 'tabla' => 'motor_situaciones_revista_arca', 'titulo' => 'Situaciones de revista', 'icono' => 'ri-calendar-check-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.actividades', 'tabla' => 'motor_actividades_arca', 'titulo' => 'Actividades ARCA', 'icono' => 'ri-briefcase-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.localidades', 'tabla' => 'motor_localidades_arca', 'titulo' => 'Localidades ARCA', 'icono' => 'ri-map-pin-line', 'cerrado' => false],
        ['ruta' => 'motor.normativa.componentes-tope', 'tabla' => 'motor_componentes_tope', 'titulo' => 'Componentes de tope', 'icono' => 'ri-pie-chart-line', 'cerrado' => true],
    ];

    public function index(): Response
    {
        $catalogos = [];
        foreach (self::CATALOGOS as $c) {
            $tieneEstado = DB::getSchemaBuilder()->hasColumn($c['tabla'], 'estado_validacion');
            $catalogos[] = $c + [
                'total' => DB::table($c['tabla'])->count(),
                'estados' => $tieneEstado ? DB::table($c['tabla'])->selectRaw('estado_validacion, count(*) n')->groupBy('estado_validacion')->pluck('n', 'estado_validacion')->all() : null,
            ];
        }

        return Inertia::render('Motor/Normativa/Index', ['catalogos' => $catalogos]);
    }
}
