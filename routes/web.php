<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\LegajosController;
use App\Http\Controllers\BajasController;
use App\Http\Controllers\SicossActivController;
use App\Http\Controllers\SicossCondicController;
use App\Http\Controllers\SicossModalidadController;
use App\Http\Controllers\SicossSituacionController;
use App\Http\Controllers\SicossObrasSocialesController;
use App\Http\Controllers\SicossSiniestroController;
use App\Http\Controllers\SicossImportarController;
use App\Http\Controllers\ArcaImportarController;
use App\Http\Controllers\SicossLocalidadesController;
use App\Http\Controllers\LsdController;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [MainController::class, 'index'])->name('dashboard');
    Route::get('/', [MainController::class, 'index'])->name('main.index');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Empleados Routes
    Route::resource('legajos', LegajosController::class);
    // Rutas adicionales para navegación
    Route::get('legajos/first', [LegajosController::class, 'first'])->name('legajos.first');
    Route::get('legajos/last', [LegajosController::class, 'last'])->name('legajos.last');
    Route::get('legajos/{id}/previous', [LegajosController::class, 'previous'])->name('legajos.previous');
    Route::get('legajos/{id}/next', [LegajosController::class, 'next'])->name('legajos.next');
    Route::get('legajos/search', [LegajosController::class, 'search'])->name('legajos.search');

    // Empleados Routes
    Route::resource('bajas', BajasController::class);
    // Rutas adicionales para navegación
    Route::get('bajas/first', [BajasController::class, 'first'])->name('bajas.first');
    Route::get('bajas/last', [BajasController::class, 'last'])->name('bajas.last');
    Route::get('bajas/{id}/previous', [BajasController::class, 'previous'])->name('bajas.previous');
    Route::get('bajas/{id}/next', [BajasController::class, 'next'])->name('bajas.next');
    Route::get('bajas/search', [BajasController::class, 'search'])->name('bajas.search');

    // Sicoss: Actividades
    Route::get('sicoss/actividades/search', [SicossActivController::class, 'search'])
        ->name('sicoss.actividades.search');

    Route::get('sicoss/actividades/first', [SicossActivController::class, 'first'])
        ->name('sicoss.actividades.first');

    Route::get('sicoss/actividades/last', [SicossActivController::class, 'last'])
        ->name('sicoss.actividades.last');

    Route::get('sicoss/actividades/{sicoss01}/previous', [SicossActivController::class, 'previous'])
        ->name('sicoss.actividades.previous');

    Route::get('sicoss/actividades/{sicoss01}/next', [SicossActivController::class, 'next'])
        ->name('sicoss.actividades.next');

    // Resource al final
    Route::resource('sicoss/actividades', SicossActivController::class)
        ->parameters(['actividades' => 'actividad'])
        ->names('sicoss.actividades');

    // Sicoss: Condiciones
    Route::get('sicoss/condiciones/search', [SicossCondicController::class, 'search'])
        ->name('sicoss.condiciones.search');

    Route::get('sicoss/condiciones/first', [SicossCondicController::class, 'first'])
        ->name('sicoss.condiciones.first');

    Route::get('sicoss/condiciones/last', [SicossCondicController::class, 'last'])
        ->name('sicoss.condiciones.last');

    Route::get('sicoss/condiciones/{sicoss05}/previous', [SicossCondicController::class, 'previous'])
        ->name('sicoss.condiciones.previous');

    Route::get('sicoss/condiciones/{sicoss05}/next', [SicossCondicController::class, 'next'])
        ->name('sicoss.condiciones.next');

    // Resource al final
    Route::resource('sicoss/condiciones', SicossCondicController::class)
        ->parameters(['condiciones' => 'condicion'])
        ->names('sicoss.condiciones');


    // Sicoss: Modalidades
    Route::get('sicoss/modalidades/search', [SicossModalidadController::class, 'search'])
        ->name('sicoss.modalidades.search');

    Route::get('sicoss/modalidades/first', [SicossModalidadController::class, 'first'])
        ->name('sicoss.modalidades.first');

    Route::get('sicoss/modalidades/last', [SicossModalidadController::class, 'last'])
        ->name('sicoss.modalidades.last');

    Route::get('sicoss/modalidades/{sicoss08}/previous', [SicossModalidadController::class, 'previous'])
        ->name('sicoss.modalidades.previous');

    Route::get('sicoss/modalidades/{sicoss08}/next', [SicossModalidadController::class, 'next'])
        ->name('sicoss.modalidades.next');

    // Resource al final
    Route::resource('sicoss/modalidades', SicossModalidadController::class)
        ->parameters(['modalidades' => 'modalidad'])
        ->names('sicoss.modalidades');


    // Sicoss: Situaciones
    Route::get('sicoss/situacion/search', [SicossSituacionController::class, 'search'])
        ->name('sicoss.situacion.search');

    Route::get('sicoss/situacion/first', [SicossSituacionController::class, 'first'])
        ->name('sicoss.situacion.first');

    Route::get('sicoss/situacion/last', [SicossSituacionController::class, 'last'])
        ->name('sicoss.situacion.last');

    Route::get('sicoss/situacion/{sicoss08}/previous', [SicossSituacionController::class, 'previous'])
        ->name('sicoss.situacion.previous');

    Route::get('sicoss/situacion/{sicoss08}/next', [SicossSituacionController::class, 'next'])
        ->name('sicoss.situacion.next');

    // Resource al final
    Route::resource('sicoss/situacion', SicossSituacionController::class)
        ->parameters(['situacion' => 'situacion'])
        ->names('sicoss.situacion');

        
    // Sicoss: Obras
    Route::get('sicoss/obras/search', [SicossObrasSocialesController::class, 'search'])
        ->name('sicoss.obras.search');

    Route::get('sicoss/obras/first', [SicossObrasSocialesController::class, 'first'])
        ->name('sicoss.obras.first');

    Route::get('sicoss/obras/last', [SicossObrasSocialesController::class, 'last'])
        ->name('sicoss.obras.last');

    Route::get('sicoss/obras/{sicoss08}/previous', [SicossObrasSocialesController::class, 'previous'])
        ->name('sicoss.obras.previous');

    Route::get('sicoss/obras/{sicoss08}/next', [SicossObrasSocialesController::class, 'next'])
        ->name('sicoss.obras.next');

    // Resource al final
    Route::resource('sicoss/obras', SicossObrasSocialesController::class)
        ->parameters(['obras' => 'obra'])
        ->names('sicoss.obras');

    // Sicoss: Siniestros
    Route::get('sicoss/siniestros/search', [SicossSiniestroController::class, 'search'])
        ->name('sicoss.siniestros.search');

    Route::get('sicoss/siniestros/first', [SicossSiniestroController::class, 'first'])
        ->name('sicoss.siniestros.first');

    Route::get('sicoss/siniestros/last', [SicossSiniestroController::class, 'last'])
        ->name('sicoss.siniestros.last');

    Route::get('sicoss/siniestros/{SicossSinie}/previous', [SicossSiniestroController::class, 'previous'])
        ->name('sicoss.siniestros.previous');

    Route::get('sicoss/siniestros/{SicossSinie}/next', [SicossSiniestroController::class, 'next'])
        ->name('sicoss.siniestros.next');

    // Resource al final
    Route::resource('sicoss/siniestros', SicossSiniestroController::class)
        ->parameters(['siniestros' => 'siniestro'])
        ->names('sicoss.siniestros');

    // Sicoss: Zonas/Localidades
    Route::get('sicoss/localidades/search', [SicossLocalidadesController::class, 'search'])
        ->name('sicoss.localidades.search');

    Route::get('sicoss/localidades/first', [SicossLocalidadesController::class, 'first'])
        ->name('sicoss.localidades.first');

    Route::get('sicoss/localidades/last', [SicossLocalidadesController::class, 'last'])
        ->name('sicoss.localidades.last');

    Route::get('sicoss/localidades/{SicossSinie}/previous', [SicossLocalidadesController::class, 'previous'])
        ->name('sicoss.localidades.previous');

    Route::get('sicoss/localidades/{SicossSinie}/next', [SicossLocalidadesController::class, 'next'])
        ->name('sicoss.localidades.next');

    // Resource al final
    Route::resource('sicoss/localidades', SicossLocalidadesController::class)
        ->parameters(['localidades' => 'siniestro'])
        ->names('sicoss.localidades');

    //--------------------------------------------
    // Sicoss importacion de datos de empleados
    //---------------------------------------------
    Route::get('sicoss/importar', [SicossImportarController::class, 'index'])
        ->name('sicoss.importar');

    Route::post('sicoss/importar2', [SicossImportarController::class, 'importar'])
        ->name('sicoss.importar2');

    Route::get('/import/status', function () {
        return response()->json([
            'ok' => \App\Models\ImportLiquidacionOk::count(),
            'err' => \App\Models\ImportLiquidacionErr::count(),
        ]);
    });

    // Importaciones
    Route::get('/sicoss/importar/exportar-ok', [SicossImportarController::class, 'exportarOk'])->name('sicoss.importar.exportarOk');
    Route::get('/sicoss/importar/exportar-err', [SicossImportarController::class, 'exportarErr'])->name('sicoss.importar.exportarErr');
    
    Route::get('/sicoss/importar/resumen', [SicossImportarController::class, 'resumenLiq'])->name('sicoss.importar.resumen');
    Route::get('/sicoss/importar/resumen/export-xlsx', [SicossImportarController::class, 'resumenLiqExportXlsx'])->name('sicoss.importar.resumen.export.xlsx');

    Route::get('/sicoss/import-resultados', [SicossImportarController::class, 'resultadosImport'])->name('sicoss.import.resultados');

    //--------------------------------------------
    // ARCA importacion de conceptos
    //---------------------------------------------
    Route::get('arca/importar', [ArcaImportarController::class, 'index'])
        ->name('arca.importar');

    Route::get('arca/empresa/{id}/cuit', [ArcaImportarController::class, 'obtenerCuit'])
        ->name('arca.empresa.cuit');

    Route::post('arca/importar2', [ArcaImportarController::class, 'importar'])
        ->name('arca.importar2');

    Route::get('/import/status', function () {
        return response()->json([
            'ok' => \App\Models\ImportLiquidacionOk::count(),
            'err' => \App\Models\ImportLiquidacionErr::count(),
        ]);
    });

    // Importaciones
    Route::get('/arca/importar/exportar-ok', [ArcaImportarController::class, 'exportarOk'])->name('arca.importar.exportarOk');
    Route::get('/arca/importar/exportar-err', [ArcaImportarController::class, 'exportarErr'])->name('arca.importar.exportarErr');
    
    Route::get('/arca/importar/resumen', [ArcaImportarController::class, 'resumenLiq'])->name('arca.importar.resumen');
    Route::get('/arca/importar/resumen/export-xlsx', [ArcaImportarController::class, 'resumenLiqExportXlsx'])->name('arca.importar.resumen.export.xlsx');
    Route::get('/arca/import-resultados', [ArcaImportarController::class, 'resultadosImport'])->name('arca.import.resultados');

    //--------------------------------------------
    // Importacion de liquidaciones (BASEDAT)
    //---------------------------------------------
    Route::get('basedat/importar', [SicossImportarController::class, 'index'])
        ->name('basedat.importar');

    Route::post('basedat/importar2', [SicossImportarController::class, 'importar'])
        ->name('basedat.importar2');

    Route::get('/basedat/import/status', function () {
        return response()->json([
            'ok' => \App\Models\ImportLiquidacionOk::count(),
            'err' => \App\Models\ImportLiquidacionErr::count(),
        ]);
    });

    // Importaciones
    Route::get('/basedat/importar/exportar-ok', [SicossImportarController::class, 'exportarOk'])->name('basedat.importar.exportarOk');
    Route::get('/basedat/importar/exportar-err', [SicossImportarController::class, 'exportarErr'])->name('basedat.importar.exportarErr');
    
    Route::get('/basedat/importar/resumen', [SicossImportarController::class, 'resumenLiq'])->name('basedat.importar.resumen');
    Route::get('/basedat/importar/resumen/export-xlsx', [SicossImportarController::class, 'resumenLiqExportXlsx'])->name('basedat.importar.resumen.export.xlsx');

    Route::get('/basedat/import-resultados', [SicossImportarController::class, 'resultadosImport'])->name('basedat.import.resultados');

    //--------------------------------------------
    // Libro de Sueldo Digital (LSD)
    //---------------------------------------------
    Route::prefix('lsd')->name('lsd.')->group(function () {
        Route::get('/generar', [LsdController::class, 'generar'])->name('generar');
        Route::post('/generar-emision', [LsdController::class, 'generarEmision'])->name('generar.emision');
        Route::get('/emision/{id}', [LsdController::class, 'obtenerEmision'])->name('emision');
        Route::put('/emision/{id}/estado', [LsdController::class, 'actualizarEstado'])->name('emision.estado');
        Route::get('/listar', [LsdController::class, 'listar'])->name('listar');
        Route::delete('/emision/{id}', [LsdController::class, 'eliminar'])->name('emision.eliminar');
    });

    
});

require __DIR__.'/auth.php';
