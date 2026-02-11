<?php

namespace App\Http\Controllers;

use App\Models\Sicoss01;    // Actividades sicoss
use App\Models\Sicoss05;    // Condiciones sicoss
use App\Models\Sicoss08;    // Modalidades sicoss
use App\Models\Sicoss12;    // Situaciones sicoss
use App\Models\SicossObras;
use App\Models\Sue001;      // Empleados activos
use App\Models\Sue086;      // Empresas
use App\Models\ImportConceptosArcaOk;
use App\Models\ImportConceptosArcaErr;
use App\Exports\ImportConceptosOkExport;
use App\Exports\ImportConceptosErrExport;
use DateTime;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ResumenLiquidacionExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Imports\NominasImport;
use App\Imports\ConceptosArcaImport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class ArcaImportarController extends Controller
{
    public function index()
    {
        $legajo = null;
        $agregar = False;
        $edicion = False;    // True: Muestra botones Grabar - Cancelar   //  False: Muestra botones: Agregar, Editar, Borrar
        $active = 65;
        //$now = Carbon::now();
        $alta = '';
        $rol = '';
        $id = null;
        $direction = null;
        $periodo2 = "2026/01";

        // Obtener el usuario actual autenticado
        $user = auth()->user();

        // Recupero roles del usuario
        $roles = $user->getRoleNames(); // Retorna una colección de roles
        if ($roles) {
            $rol = $roles->first();
        }

        // Busco registro
        if ($id == null or $id == 0) {
            //------------------------------
            //      Primer registro
            //------------------------------
            $legajo = Sue001::orderBy('codigo')
                ->first();      // find($id);
        } else {
            //------------------------------
            //      Primer registro
            //------------------------------
            if ($direction == null) {
                $legajo = Sue001::find($id);
                if ($legajo == null) {
                    $legajo = Sue001::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sue001;
                }
            } elseif ($direction == -1) {
                //------------------------------
                //      Registro anterior
                //------------------------------
                $legajo = Sue001::where('id', '<', $id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($legajo == null) {
                    $legajo = Sue001::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sue001;
                }
            } elseif ($direction == 1) {
                //------------------------------
                //      Registro siguiente
                //------------------------------
                $legajo = Sue001::find($id);
                if ($legajo == null) {
                    $legajo = Sue001::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sue001;
                }

            } elseif ($direction == -9) {
                //------------------------------
                //      Ultimo registro
                //------------------------------
                $legajo = Sue001::latest()->first();

                if ($legajo == null) {
                    $legajo = Sue001::orderBy('codigo')
                        ->first();      // first(); // find($id);     // dd($legajo);   // $legajo = new Sue001;
                }
            }
        }

        // Si a pesar de todos los controles $legajo es null es porque no hay registros
        if ($legajo == null)
            $legajo = new Sue001;


        // Tablas complementarias
        $actividades = Sicoss01::orderBy('codigo')->get();
        $condiciones = Sicoss05::orderBy('codigo')->get();
        $contrataciones = Sicoss08::orderBy('codigo')->get();
        $situaciones = Sicoss12::orderBy('codigo')->get();
        $obras2 = SicossObras::orderBy('codigo')->get();
        $empresas = Sue086::orderBy('codigo')->get();
        
        // Obtener CUIT de la primera empresa por defecto
        $cuit = $empresas->first()?->cuit ?? '';
        //$zonas = SicossZona::orderBy('codigo')->get();
        //$sinie = SicossSinie::orderBy('codigo')->get();

        return view('arca.importar')->with(compact(
            'legajo', 'active', 'agregar', 'edicion', 'actividades', 'condiciones', 'contrataciones', 'periodo2', 'user', 'rol', 'empresas', 'cuit'
        ));
    }

    public function obtenerCuit($id)
    {
        $empresa = Sue086::find($id);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        return response()->json(['cuit' => $empresa->cuit]);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:txt,csv|max:2048',
            ], [
            'file.required' => 'Por favor, selecciona un archivo para subir.',
            'file.mimes' => 'El archivo debe ser un archivo de texto (.txt) o CSV (.csv).',
            'file.max' => 'El archivo no debe ser mayor a 2 MB.', // 1024 KB = 1 MB
        ]);

        $vererrores = 'style=display: block';
        $count = 0;
        $rechazados = 0;
        $total = 0;
        $tipoliq = 1;
        $agregar = True;
        $edicion = True;
        $active = 73;

        session()->flash('errorNro', '0');

        $fechaLiq = now();

        //$tipoliq = (int) $request->input('tipoliq');
        $idEmpresa = (int) $request->input('empresa');

        $comenta1 = $request->input('comenta1');
        $nom_arch = $request->input('nom_arch');
        $tam_arch = $request->input('tam_arch');

        if ($comenta1 == null) {
            $comenta1 = '';
        }

        // ⚡️ Limpio los logs antes de correr el import:
        ImportConceptosArcaOk::truncate();
        ImportConceptosArcaErr::truncate();

        // Limites de tiempo desactivoados
        set_time_limit(0); // sin límite (o pon 300 para 5 min)
        ini_set('max_execution_time', '300'); // opcional si set_time_limit está deshabilitado
        ini_set('memory_limit', '512M'); // por si ayuda
        DB::disableQueryLog(); // reduce consumo de memoria

        // Almacenar el archivo
        $file = $request->file('file')->store('imports');
        $fullPath = Storage::path($file);

        // Validar existencia y lectura del archivo
        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'msg' => 'El archivo no existe en el path calculado',
                'fullPath' => $fullPath,
            ], 422);
        }

        if (!is_readable($fullPath)) {
            return response()->json([
                'success' => false,
                'msg' => 'El archivo existe pero no es legible (permisos)',
                'fullPath' => $fullPath,
            ], 422);
        }

        // ⚡️ Limpio los logs antes de correr el import:
        ImportConceptosArcaOk::truncate();
        ImportConceptosArcaErr::truncate();

        try {
            // Usar ConceptosArcaImport para procesar archivos de texto
            $import = new ConceptosArcaImport(
                $fullPath,
                $idEmpresa,
                $nom_arch,
                $tam_arch
            );

            // Ejecutar la importación
            $import->execute();

            // Contadores desde el import
            $count = $import->getRowCount();
            $rechazados = $import->getRejectedCount();
            $total = $import->getTotalCount();

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error procesando archivo: ' . $e->getMessage(),
                'fullPath' => $fullPath,
            ], 422);
        }

        // 👉 Si la petición viene por AJAX (fetch), respondemos en JSON
        if ($request->ajax()) {
            return response()->json([
                'success'     => true,
                'total'       => $total,
                'count'       => $count,
                'rechazados'  => $rechazados,
                'msg'         => "Se importaron $count registros y se rechazaron $rechazados.",
            ]);
        }

        return response()->json([
            'success'     => false,
            'total'       => 0,
            'count'       => 0,
            'rechazados'  => 0,
            'msg'         => "Se detectó un error desconocido.",
        ]);
    }

    public function resultadosImport()
    {
        $count = ImportConceptosArcaOk::count();
        $rechazados = ImportConceptosArcaErr::count();
        $total = $count + $rechazados;

        return response()->json([
            'success'    => true,
            'total'      => $total,
            'count'      => $count,
            'rechazados' => $rechazados,
        ]);
    }

    public function exportarOk() {
        return Excel::download(new ImportConceptosOkExport, 'importacion_liquidacion.xlsx');
    }

    public function exportarErr() {
        return Excel::download(new ImportConceptosErrExport, 'importacion_rechazados.xlsx');
    }
}
