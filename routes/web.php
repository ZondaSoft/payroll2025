<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\LegajosController;
use App\Http\Controllers\BajasController;
use App\Http\Controllers\ConveniosController;
use App\Http\Controllers\SicossActivController;
use App\Http\Controllers\SicossCondicController;
use App\Http\Controllers\SicossModalidadController;
use App\Http\Controllers\SicossSituacionController;
use App\Http\Controllers\SicossObrasSocialesController;
use App\Http\Controllers\SicossSiniestroController;
use App\Http\Controllers\SicossImportarController;
use App\Http\Controllers\ArcaImportarController;
use App\Http\Controllers\SicossLocalidadesController;
use App\Http\Controllers\LiquidacionImportarController;
use App\Http\Controllers\LsdController;
use App\Http\Controllers\LsdImporteDetraerController;
use App\Http\Controllers\LsdTopeController;
use App\Http\Controllers\ConceptosLiquidacionController;
use App\Http\Controllers\LiquidacionIndividualController;
use App\Http\Controllers\LiquidacionCorreccionesController;
use App\Http\Controllers\PeriodosController;
use App\Http\Controllers\GruposEmpresariosController;
use App\Http\Controllers\CentrosCostoController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\CuadrillasController;
use App\Http\Controllers\SindicatosController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\TiposContratoController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\ConceptosArcaController;
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

// Devuelve el token CSRF vigente y refresca la cookie XSRF-TOKEN. Lo usa el reintento automático
// de axios ante un 419 (CSRF token mismatch) para recuperar la sesión sin recargar la página.
// FUERA del grupo 'auth' a propósito: así el refresco funciona aunque el token haya rotado
// (solo necesita la sesión 'web', no estar autenticado); si estuviera en 'auth', el reintento
// no podría recuperarse cuando más se necesita.
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf.token');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [MainController::class, 'index'])->name('dashboard');
    Route::get('/', [MainController::class, 'index'])->name('main.index');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Empleados Routes - rutas literales ANTES del resource para evitar que {legajo} las capture
    Route::get('legajos/first', [LegajosController::class, 'first'])->name('legajos.first');
    Route::get('legajos/last', [LegajosController::class, 'last'])->name('legajos.last');
    Route::get('legajos/search', [LegajosController::class, 'search'])->name('legajos.search');
    Route::resource('legajos', LegajosController::class);
    // Rutas adicionales para navegación
    Route::get('legajos/{id}/previous', [LegajosController::class, 'previous'])->name('legajos.previous');
    Route::get('legajos/{id}/next', [LegajosController::class, 'next'])->name('legajos.next');

    // Empleados Routes - rutas literales ANTES del resource
    Route::get('bajas/first', [BajasController::class, 'first'])->name('bajas.first');
    Route::get('bajas/last', [BajasController::class, 'last'])->name('bajas.last');
    Route::get('bajas/search', [BajasController::class, 'search'])->name('bajas.search');
    Route::resource('bajas', BajasController::class);
    // Rutas adicionales para navegación
    Route::get('bajas/{id}/previous', [BajasController::class, 'previous'])->name('bajas.previous');
    Route::get('bajas/{id}/next', [BajasController::class, 'next'])->name('bajas.next');

    // Convenios Colectivos de Trabajo (CCT) - rutas literales ANTES del resource
    Route::get('convenios/first', [ConveniosController::class, 'first'])->name('convenios.first');
    Route::get('convenios/last', [ConveniosController::class, 'last'])->name('convenios.last');
    Route::get('convenios/search', [ConveniosController::class, 'search'])->name('convenios.search');
    Route::get('convenios/{id}/previous', [ConveniosController::class, 'previous'])->name('convenios.previous');
    Route::get('convenios/{id}/next', [ConveniosController::class, 'next'])->name('convenios.next');
    Route::resource('convenios', ConveniosController::class)
        ->parameters(['convenios' => 'convenio']);

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
        ->parameters(['localidades' => 'localidad'])
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
    Route::get('basedat/importar', [LiquidacionImportarController::class, 'index'])
        ->name('basedat.importar');

    Route::post('basedat/importar2', [LiquidacionImportarController::class, 'importar'])
        ->name('basedat.importar2');

    Route::get('/basedat/import/status', function () {
        return response()->json([
            'ok' => \App\Models\ImportLiquidacionOk::count(),
            'err' => \App\Models\ImportLiquidacionErr::count(),
        ]);
    });

    // Importaciones
    Route::get('/basedat/importar/exportar-ok', [LiquidacionImportarController::class, 'exportarOk'])->name('basedat.importar.exportarOk');
    Route::get('/basedat/importar/exportar-err', [LiquidacionImportarController::class, 'exportarErr'])->name('basedat.importar.exportarErr');
    
    Route::get('/basedat/importar/resumen', [LiquidacionImportarController::class, 'resumenLiq'])->name('basedat.importar.resumen');
    Route::get('/basedat/importar/resumen/export-xlsx', [LiquidacionImportarController::class, 'resumenLiqExportXlsx'])->name('basedat.importar.resumen.export.xlsx');

    Route::get('/basedat/import-resultados', [LiquidacionImportarController::class, 'resultadosImport'])->name('basedat.import.resultados');

    //--------------------------------------------
    // Libro de Sueldo Digital (LSD)
    //---------------------------------------------
    Route::prefix('lsd')->name('lsd.')->group(function () {
        Route::get('/generar', [LsdController::class, 'generar'])->name('generar');
        Route::post('/generar-emision', [LsdController::class, 'generarEmision'])->name('generar.emision');
        Route::post('/generar-conceptos', [LsdController::class, 'generarConceptos'])->name('generar.conceptos');
        Route::post('/ajustar-aportes', [LsdController::class, 'ajustarAportes'])->name('ajustar.aportes');
        Route::get('/emision/{id}/detalle', [LsdController::class, 'detalle'])->name('emision.detalle');
        Route::get('/emision/{id}/detalle/{concepto}', [LsdController::class, 'detalleConcepto'])->name('emision.detalle.concepto');
        Route::post('/emision/{id}/detalle/{concepto}/editar-codigo', [LsdController::class, 'editarCodigoConcepto'])->name('emision.detalle.concepto.editar_codigo');
        Route::get('/emision/{id}/download', [LsdController::class, 'download'])->name('emision.download');
        Route::get('/emision/{id}', [LsdController::class, 'obtenerEmision'])->name('emision');
        Route::put('/emision/{id}/estado', [LsdController::class, 'actualizarEstado'])->name('emision.estado');
        Route::get('/listar', [LsdController::class, 'listar'])->name('listar');
        Route::delete('/emision/{id}', [LsdController::class, 'eliminar'])->name('emision.eliminar');
    });

    //--------------------------------------------
    // SICOSS: Importes a detraer (Ley 27.430)
    //---------------------------------------------
    Route::resource('sicoss/importes-detraer', LsdImporteDetraerController::class)
        ->parameters(['importes-detraer' => 'importe'])
        ->names('sicoss.importes-detraer')
        ->except(['show']);

    //--------------------------------------------
    // SICOSS: Topes máximos de base imponible para aportes (BI 1/4/5)
    //---------------------------------------------
    Route::resource('sicoss/topes', LsdTopeController::class)
        ->parameters(['topes' => 'tope'])
        ->names('sicoss.topes')
        ->except(['show']);

    //--------------------------------------------
    // Config: Parametrizaciones (rangos sue089s)
    //---------------------------------------------
    Route::resource('config/parametros', ParametrosController::class)
        ->parameters(['parametros' => 'parametro'])
        ->names('config.parametros')
        ->except(['show']);

    //--------------------------------------------
    // ARCA: Conceptos (CRUD tabla conceptosarcas)
    //---------------------------------------------
    Route::get('arca/conceptos/search', [ConceptosArcaController::class, 'search'])
        ->name('arca.conceptos.search');
    Route::get('arca/conceptos/first', [ConceptosArcaController::class, 'first'])
        ->name('arca.conceptos.first');
    Route::get('arca/conceptos/last', [ConceptosArcaController::class, 'last'])
        ->name('arca.conceptos.last');
    Route::get('arca/conceptos/{id}/previous', [ConceptosArcaController::class, 'previous'])
        ->name('arca.conceptos.previous');
    Route::get('arca/conceptos/{id}/next', [ConceptosArcaController::class, 'next'])
        ->name('arca.conceptos.next');
    Route::resource('arca/conceptos', ConceptosArcaController::class)
        ->parameters(['conceptos' => 'concepto'])
        ->names('arca.conceptos');

    //--------------------------------------------
    // Liquidación: Períodos
    //---------------------------------------------
    Route::get('liquidacion/periodos/search', [PeriodosController::class, 'search'])
        ->name('liquidacion.periodos.search');

    Route::get('liquidacion/periodos/first', [PeriodosController::class, 'first'])
        ->name('liquidacion.periodos.first');

    Route::get('liquidacion/periodos/last', [PeriodosController::class, 'last'])
        ->name('liquidacion.periodos.last');

    Route::get('liquidacion/periodos/{periodo}/previous', [PeriodosController::class, 'previous'])
        ->name('liquidacion.periodos.previous');

    Route::get('liquidacion/periodos/{periodo}/next', [PeriodosController::class, 'next'])
        ->name('liquidacion.periodos.next');

    Route::get('liquidacion/periodos/create', [PeriodosController::class, 'create'])
        ->name('liquidacion.periodos.create');

    Route::get('liquidacion/periodos/{periodo}/edit', [PeriodosController::class, 'edit'])
        ->name('liquidacion.periodos.edit');

    Route::get('liquidacion/periodos/{periodo}/show', [PeriodosController::class, 'show'])
        ->name('liquidacion.periodos.show');

    Route::get('liquidacion/periodos/{id?}/{direction?}', [PeriodosController::class, 'index'])
        ->name('liquidacion.periodos.index');

    Route::resource('liquidacion/periodos', PeriodosController::class)
        ->parameters(['periodos' => 'periodo'])
        ->except(['index', 'create', 'edit', 'show'])
        ->names([
            'store'   => 'liquidacion.periodos.store',
            'update'  => 'liquidacion.periodos.update',
            'destroy' => 'liquidacion.periodos.destroy',
        ]);

    //--------------------------------------------
    // Liquidación: Conceptos
    //---------------------------------------------
    Route::get('liquidacion/conceptos/proximoCodigo', [ConceptosLiquidacionController::class, 'obtenerProximoCodigo'])
        ->name('liquidacion.conceptos.proximoCodigo');

    Route::get('liquidacion/conceptos/buscarConceptosArca', [ConceptosLiquidacionController::class, 'buscarConceptosArca'])
        ->name('liquidacion.conceptos.buscarConceptosArca');

    Route::get('liquidacion/conceptos/search', [ConceptosLiquidacionController::class, 'search'])
        ->name('liquidacion.conceptos.search');

    Route::get('liquidacion/conceptos/first', [ConceptosLiquidacionController::class, 'first'])
        ->name('liquidacion.conceptos.first');

    Route::get('liquidacion/conceptos/last', [ConceptosLiquidacionController::class, 'last'])
        ->name('liquidacion.conceptos.last');

    Route::get('liquidacion/conceptos/{concepto}/previous', [ConceptosLiquidacionController::class, 'previous'])
        ->name('liquidacion.conceptos.previous');

    Route::get('liquidacion/conceptos/{concepto}/next', [ConceptosLiquidacionController::class, 'next'])
        ->name('liquidacion.conceptos.next');

    // Rutas explícitas ANTES del wildcard para evitar que {id?} capture 'create'
    Route::get('liquidacion/conceptos/create', [ConceptosLiquidacionController::class, 'create'])
        ->name('liquidacion.conceptos.create');

    Route::get('liquidacion/conceptos/{concepto}/edit', [ConceptosLiquidacionController::class, 'edit'])
        ->name('liquidacion.conceptos.edit');

    Route::get('liquidacion/conceptos/{concepto}/show', [ConceptosLiquidacionController::class, 'show'])
        ->name('liquidacion.conceptos.show');

    // Wildcard al final para que no intercepte rutas estáticas como 'create'
    Route::get('liquidacion/conceptos/{id?}/{direction?}', [ConceptosLiquidacionController::class, 'index'])
        ->name('liquidacion.conceptos.index');

    Route::resource('liquidacion/conceptos', ConceptosLiquidacionController::class)
        ->parameters(['conceptos' => 'concepto'])
        ->except(['index','create','edit','show'])
        ->names([
            'store'   => 'liquidacion.conceptos.store',
            'update'  => 'liquidacion.conceptos.update',
            'destroy' => 'liquidacion.conceptos.destroy',
        ]);

    //--------------------------------------------
    // Liquidación Individual
    //---------------------------------------------
    Route::get('liquidacion/individual', [LiquidacionIndividualController::class, 'index'])
        ->name('liquidacion.individual.index');
    Route::get('liquidacion/individual/lista/{periodo}', [LiquidacionIndividualController::class, 'lista'])
        ->name('liquidacion.individual.lista');
    Route::get('liquidacion/individual/lista/{periodo}/pdf', [LiquidacionIndividualController::class, 'listaPdf'])
        ->name('liquidacion.individual.lista.pdf');
    Route::delete('liquidacion/individual/eliminar', [LiquidacionIndividualController::class, 'eliminar'])
        ->name('liquidacion.individual.eliminar');
    Route::post('liquidacion/individual/actualizar-concepto', [LiquidacionIndividualController::class, 'actualizarConcepto'])
        ->name('liquidacion.individual.actualizarConcepto');

    // Visor de correcciones / ajustes automáticos sobre la liquidación
    Route::get('liquidacion/correcciones', [LiquidacionCorreccionesController::class, 'index'])
        ->name('liquidacion.correcciones.index');

    //--------------------------------------------
    // Grupos Empresarios (Sue086)
    //---------------------------------------------
    Route::get('grupos-empresarios/search', [GruposEmpresariosController::class, 'search'])
        ->name('grupos.empresarios.search');

    Route::get('grupos-empresarios/first', [GruposEmpresariosController::class, 'first'])
        ->name('grupos.empresarios.first');

    Route::get('grupos-empresarios/last', [GruposEmpresariosController::class, 'last'])
        ->name('grupos.empresarios.last');

    Route::get('grupos-empresarios/{id}/previous', [GruposEmpresariosController::class, 'previous'])
        ->name('grupos.empresarios.previous');

    Route::get('grupos-empresarios/{id}/next', [GruposEmpresariosController::class, 'next'])
        ->name('grupos.empresarios.next');

    Route::resource('grupos-empresarios', GruposEmpresariosController::class)
        ->parameters(['grupos-empresarios' => 'grupo'])
        ->names('grupos.empresarios');

    //---------------------------------------------
    // Catálogos de RR.HH. (ABM bajo "Grupo empresario")
    //---------------------------------------------

    // Centros de costo (Sue030)
    Route::get('centros-costo/search', [CentrosCostoController::class, 'search'])->name('centros.costo.search');
    Route::get('centros-costo/first', [CentrosCostoController::class, 'first'])->name('centros.costo.first');
    Route::get('centros-costo/last', [CentrosCostoController::class, 'last'])->name('centros.costo.last');
    Route::get('centros-costo/{id}/previous', [CentrosCostoController::class, 'previous'])->name('centros.costo.previous');
    Route::get('centros-costo/{id}/next', [CentrosCostoController::class, 'next'])->name('centros.costo.next');
    Route::resource('centros-costo', CentrosCostoController::class)
        ->parameters(['centros-costo' => 'registro'])->names('centros.costo');

    // Sectores (Sue011)
    Route::get('sectores/search', [SectoresController::class, 'search'])->name('sectores.search');
    Route::get('sectores/first', [SectoresController::class, 'first'])->name('sectores.first');
    Route::get('sectores/last', [SectoresController::class, 'last'])->name('sectores.last');
    Route::get('sectores/{id}/previous', [SectoresController::class, 'previous'])->name('sectores.previous');
    Route::get('sectores/{id}/next', [SectoresController::class, 'next'])->name('sectores.next');
    Route::resource('sectores', SectoresController::class)
        ->parameters(['sectores' => 'registro']);

    // Cuadrillas (Sue054)
    Route::get('cuadrillas/search', [CuadrillasController::class, 'search'])->name('cuadrillas.search');
    Route::get('cuadrillas/first', [CuadrillasController::class, 'first'])->name('cuadrillas.first');
    Route::get('cuadrillas/last', [CuadrillasController::class, 'last'])->name('cuadrillas.last');
    Route::get('cuadrillas/{id}/previous', [CuadrillasController::class, 'previous'])->name('cuadrillas.previous');
    Route::get('cuadrillas/{id}/next', [CuadrillasController::class, 'next'])->name('cuadrillas.next');
    Route::resource('cuadrillas', CuadrillasController::class)
        ->parameters(['cuadrillas' => 'registro']);

    // Sindicatos (Sue015)
    Route::get('sindicatos/search', [SindicatosController::class, 'search'])->name('sindicatos.search');
    Route::get('sindicatos/first', [SindicatosController::class, 'first'])->name('sindicatos.first');
    Route::get('sindicatos/last', [SindicatosController::class, 'last'])->name('sindicatos.last');
    Route::get('sindicatos/{id}/previous', [SindicatosController::class, 'previous'])->name('sindicatos.previous');
    Route::get('sindicatos/{id}/next', [SindicatosController::class, 'next'])->name('sindicatos.next');
    Route::resource('sindicatos', SindicatosController::class)
        ->parameters(['sindicatos' => 'registro']);

    // Categorías (Sue006)
    Route::get('categorias/search', [CategoriasController::class, 'search'])->name('categorias.search');
    Route::get('categorias/first', [CategoriasController::class, 'first'])->name('categorias.first');
    Route::get('categorias/last', [CategoriasController::class, 'last'])->name('categorias.last');
    Route::get('categorias/{id}/previous', [CategoriasController::class, 'previous'])->name('categorias.previous');
    Route::get('categorias/{id}/next', [CategoriasController::class, 'next'])->name('categorias.next');
    Route::resource('categorias', CategoriasController::class)
        ->parameters(['categorias' => 'registro']);

    // Tipos de contrato (Sue107)
    Route::get('tipos-contrato/search', [TiposContratoController::class, 'search'])->name('tipos.contrato.search');
    Route::get('tipos-contrato/first', [TiposContratoController::class, 'first'])->name('tipos.contrato.first');
    Route::get('tipos-contrato/last', [TiposContratoController::class, 'last'])->name('tipos.contrato.last');
    Route::get('tipos-contrato/{id}/previous', [TiposContratoController::class, 'previous'])->name('tipos.contrato.previous');
    Route::get('tipos-contrato/{id}/next', [TiposContratoController::class, 'next'])->name('tipos.contrato.next');
    Route::resource('tipos-contrato', TiposContratoController::class)
        ->parameters(['tipos-contrato' => 'registro'])->names('tipos.contrato');

});

require __DIR__.'/auth.php';
