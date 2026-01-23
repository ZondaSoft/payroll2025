<?php

namespace App\Http\Controllers;

use App\Models\Sicoss01;    // Actividades sicoss
use App\Models\Sicoss05;    // Condiciones sicoss
use App\Models\Sicoss08;    // Modalidades sicoss
use App\Models\Sicoss12;    // Situaciones sicoss
use App\Models\SicossObras;
use App\Models\Sue001;      // Empleados activos
use App\Models\Sue086;      // Empresas
use App\Models\ImportLiquidacionOk;
use App\Models\ImportLiquidacionErr;
use App\Exports\ImportSicossErrExport;
use App\Exports\ImportSicossOkExport;
use App\Models\ImportSicossOk;
use App\Models\ImportSicossErr;
use DateTime;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ResumenLiquidacionExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Imports\NominasImport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Datoempr;

class SicossImportarController extends Controller
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
        //$zonas = SicossZona::orderBy('codigo')->get();
        //$sinie = SicossSinie::orderBy('codigo')->get();

        return view('sicoss.importar')->with(compact(
            'legajo', 'active', 'agregar', 'edicion', 'actividades', 'condiciones', 'contrataciones', 'periodo2', 'user', 'rol', 'empresas'
        ));
    }

    public function importar(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx|max:2048',
            ], [
            'file.required' => 'Por favor, selecciona un archivo para subir.',
            'file.mimes' => 'El archivo debe ser un documento Excel con extensión .xls o .xlsx.',
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

        // Normalizaciones
        $periodoIn = preg_replace('/\D/', '', $request->input('periodo2')); // quita '/'
        // ahora $periodo = YYYYMM
        if (strlen($periodoIn) === 6) {
            $periodo = $periodoIn; // p.ej 2025/06
        } else {
            return response()->json(['success'=>false,'$periodoIn'=>$periodoIn,'msg'=>'Periodo inválido'], 422);
        }

        $fechaLiq = now();

        $tipoliq = (int) $request->input('tipoliq');

        $comenta1 = $request->input('comenta1');
        $nom_arch = $request->input('nom_arch');
        $tam_arch = $request->input('tam_arch');

        if ($comenta1 == null) {
            $comenta1 = '';
        }

        // ⚡️ Limpio los logs antes de correr el import:
        ImportLiquidacionOk::truncate();
        ImportLiquidacionErr::truncate();

        // Limites de tiempo desactivoados
        set_time_limit(0); // sin límite (o pon 300 para 5 min)
        ini_set('max_execution_time', '300'); // opcional si set_time_limit está deshabilitado
        ini_set('memory_limit', '512M'); // por si ayuda
        DB::disableQueryLog(); // reduce consumo de memoria

        // Proceso el importador
        //$file = $request->file('file')->store('imports');
        //$fullPath = storage_path('app/' . $file);
        $file = $request->file('file')->store('imports'); // usa el disk default (private en tu caso)
        $fullPath = Storage::path($file);                 // ✅ te da el path real correcto

        // Diagnostico
        logger()->info('IMPORT DEBUG', [
            'stored' => $file ?? null,
            'fullPath' => $fullPath,
            'exists_file_exists' => file_exists($fullPath),
            'exists_storage' => \Illuminate\Support\Facades\Storage::disk('local')->exists($file ?? ''),
            'filesize' => file_exists($fullPath) ? filesize($fullPath) : null,
            'is_readable' => is_readable($fullPath),
            'mime' => file_exists($fullPath) ? mime_content_type($fullPath) : null,
        ]);

        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'msg' => 'NO EXISTE el archivo en el path calculado',
                'fullPath' => $fullPath,
            ], 422);
        }

        if (!is_readable($fullPath)) {
            return response()->json([
                'success' => false,
                'msg' => 'El archivo existe pero NO es legible (permisos)',
                'fullPath' => $fullPath,
            ], 422);
        }

        // Detectar hoja activa
        //$spreadsheet = IOFactory::load($fullPath);
        //$activeTitle = $spreadsheet->getActiveSheet()->getTitle();
        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true); // más liviano
            $spreadsheet = $reader->load($fullPath);
            $activeTitle = $spreadsheet->getActiveSheet()->getTitle();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'msg' => 'No se pudo abrir el Excel (PhpSpreadsheet)',
                'error' => $e->getMessage(),
                'fullPath' => $fullPath,
            ], 422);
        }

        // Tomar la hoja activa solo para el título (sin recalcular fórmulas)
        //$reader = IOFactory::createReaderForFile($fullPath);
        //$reader->setReadDataOnly(true); // lee valores, no estilos
        //$spreadsheet = $reader->load($fullPath);
        //$activeTitle = $spreadsheet->getActiveSheet()->getTitle();

        $import = new NominasImport(
            $activeTitle,
            periodo: $periodo,         // YYYYMM
            tipoliq: $tipoliq,        // int
            nom_arch: $nom_arch,
            tam_arch: $tam_arch
        );

        //ImportarLiquidacionJob::dispatch($file);
        //Excel::import($import, $fullPath);
        //Excel::import($import, $fullPath, null, \Maatwebsite\Excel\Excel::XLSX, [
        //    'pre_calculate_formulas' => false
        //]);
        $ext = strtolower($request->file('file')->getClientOriginalExtension());

        $excelType = match ($ext) {
            'xls'  => \Maatwebsite\Excel\Excel::XLS,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'xlsm' => \Maatwebsite\Excel\Excel::XLSX, // suele abrir igual
            default => \Maatwebsite\Excel\Excel::XLSX,
        };

        Excel::import($import, $fullPath, null, $excelType, [
            'pre_calculate_formulas' => false
        ]);

        // Contadores desde el import, sin depender de la BD
        $count = $import->getRowCount();
        $rechazados = $import->getRejectedCount();
        $total = $count + $rechazados;

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
        $count = ImportLiquidacionOk::count();
        $rechazados = ImportLiquidacionErr::count();
        $total = $count + $rechazados;

        return response()->json([
            'success'    => true,
            'total'      => $total,
            'count'      => $count,
            'rechazados' => $rechazados,
        ]);
    }

    public function exportarOk() {
        return Excel::download(new ImportSicossOkExport, 'importacion_liquidacion.xlsx');
    }

    public function exportarErr() {
        return Excel::download(new ImportSicossErrExport, 'importacion_rechazados.xlsx');
    }
    
    
}
