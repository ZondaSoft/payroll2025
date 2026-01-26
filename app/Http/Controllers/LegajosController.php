<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sue001;
use App\Models\Sue002;
use App\Models\Sue019;
use App\Models\Sue086;
use App\Models\Sue011;
use App\Models\Sue005;
use App\Models\Sue010;
use App\Models\Sue030;
use App\Models\Sue014;
use App\Models\Sue006;
use App\Models\Sue054;
use App\Models\Sue009;
use App\Models\Sue015;
use App\Models\Sue007;
use App\Models\Sue107;
use App\Models\Sue094;
use App\Models\Sicoss01;    // Actividades sicoss
use App\Models\Sicoss05;
use App\Models\Sicoss08;
use App\Models\Sicoss12;
use App\Models\SicossObras;
use App\Models\SicossZona;
use App\Models\SicossSinie;
use App\Models\Sue012;
use App\Models\Sue052;
use App\Models\Fza002;
use App\Models\SicossCodigosBaja;
use App\Models\Sue074;
use App\Models\Datoempr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LegajosController extends Controller
{
    /**
     * Muestra un listado de empleados
     */
    public function index($id = null, $direction = null)
    {
        $legajo = null;
        $agregar = False;
        $edicion = False;    // True: Muestra botones Grabar - Cancelar   //  False: Muestra botones: Agregar, Editar, Borrar
        $active = 65;
        //$now = Carbon::now();
        $alta = '';
        $rol = '';

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

        // Datos de la empresa
        $empresa = Datoempr::first();      // find($id);
        if ($empresa == null) {
            return redirect('/empresa/');
        }

        // Si a pesar de todos los controles $legajo es null es porque no hay registros
        if ($legajo == null)
            $legajo = new Sue001;


        // Tablas complementarias
        $localidades = Sue019::orderBy('codigo')->get();
        $grupos     = Sue086::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $sectores   = Sue011::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $situacionesLab = Sue005::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $jornadas   = Sue010::orderBy('detalle')->whereNotNull('id')->get();
        $ccostos    = Sue030::orderBy('detalle')->get();
        $jerarquias = Sue014::orderBy('detalle')->get();
        $categorias = Sue006::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $cuadrillas = Sue054::orderBy('detalle')->get();
        $obras      = Sue009::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $sindicatos = Sue015::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $convenios  = Sue007::orderBy('detalle')->get();
        $contratos = Sue107::orderBy('detalle')->get(['codigo','detalle','duracion']);
        $horarios   = Sue094::orderBy('detalle')->get();
        $actividades = Sicoss01::orderBy('codigo')->get();
        $condiciones = Sicoss05::orderBy('codigo')->get();
        $contrataciones = Sicoss08::orderBy('codigo')->get();
        $situaciones = Sicoss12::orderBy('codigo')->get();
        $obras2 = SicossObras::orderBy('codigo')->get();
        $zonas = SicossZona::orderBy('codigo')->get();
        $sinie = SicossSinie::orderBy('codigo')->get();
        $provincias = Sue012::orderBy('codigo')->where('codigo','!=','')->get();
        $capacidades = Sue052::orderBy('codigo')->paginate(12);
        $bancos = Fza002::orderBy('detalle')->get();
        $sicossCodBajas = SicossCodigosBaja::orderBy('detalle')->get();
        $historial = Sue074::orderBy('id')->where('legajo_codigo', '=', $legajo->codigo)->get();

        if ($legajo != null) {
            $legajo->usuario = 'Importación';
            //$legajo->fecha_alta = $legajo->created_at->format('d/m/Y');

            // prioriza 'alta' y cae a created_at
            $fecha = $legajo->created_at;

            $legajo->fecha_alta = $fecha
                ? Carbon::parse($fecha)->format('d/m/Y')
                : null;

            //$legajo->hora = $legajo->created_at->format('H:i');
            $legajo->hora = optional($legajo->created_at)->format('H:i');

            $familiares = Sue002::orderBy('paren')->Where('legajo', '=', $legajo->codigo)->get();
        } else {
            $familiares = new Sue002;
        }

        // dd($legajo);

        // formulario vue via inertia
        return Inertia::render('Empleados/Index', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'provincias' => $provincias,
            'grupos' => $grupos,
            'jerarquias' => $jerarquias,
            'categorias' => $categorias,
            'ccostos' => $ccostos,
            'sectores' => $sectores,
            'cuadrillas' => $cuadrillas,
            'obras' => $obras2,
            'sindicatos' => $sindicatos,
            'convenios' => $convenios,
            'contrataciones' => $contrataciones,
            'situacionesLab' => $situacionesLab,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo empleado
     */
    public function create()
    {
        return Inertia::render('Empleados/Form');
    }

    /**
     * Almacena un nuevo empleado en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|numeric|unique:empleados,dni',
            'detalle' => 'required|string|max:100',
            'nombres' => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            'sexo' => 'required|in:Masculino,Femenino,Otro',
            'email' => 'required|email|max:100|unique:empleados,email',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'fecha_ingreso' => 'required|date',
            'puesto' => 'required|string|max:100',
            'salario' => 'required|numeric|min:0',
            'estado' => 'required|boolean',
        ]);

        Sue001::create($validated);

        return redirect()->route('empleados.index')
            ->with('success', 'Empleado creado exitosamente.');
    }

    /**
     * Muestra los detalles de un empleado
     */
    public function show(Sue001 $empleado)
    {
        $legajo = $empleado;
        $agregar = False;
        $edicion = False;    // True: Muestra botones Grabar - Cancelar   //  False: Muestra botones: Agregar, Editar, Borrar
        $active = 65;
        //$now = Carbon::now();
        $alta = '';
        $rol = '';

        // Obtener el usuario actual autenticado
        $user = auth()->user();

        // Recupero roles del usuario
        $roles = $user->getRoleNames(); // Retorna una colección de roles
        if ($roles) {
            $rol = $roles->first();
        }

        // Tablas complementarias
        $localidades = Sue019::orderBy('codigo')->get();
        $grupos     = Sue086::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $sectores   = Sue011::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $situacionesLab = Sue005::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $jornadas   = Sue010::orderBy('detalle')->whereNotNull('id')->get();
        $ccostos    = Sue030::orderBy('detalle')->get();
        $jerarquias = Sue014::orderBy('detalle')->get();
        $categorias = Sue006::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $cuadrillas = Sue054::orderBy('detalle')->get();
        $obras      = Sue009::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $sindicatos = Sue015::orderBy('detalle')->whereNotNull('codigo')->where('codigo', '!=', "")->get();
        $convenios  = Sue007::orderBy('detalle')->get();
        $contratos = Sue107::orderBy('detalle')->get(['codigo','detalle','duracion']);
        $horarios   = Sue094::orderBy('detalle')->get();
        $actividades = Sicoss01::orderBy('codigo')->get();
        $condiciones = Sicoss05::orderBy('codigo')->get();
        $contrataciones = Sicoss08::orderBy('codigo')->get();
        $situaciones = Sicoss12::orderBy('codigo')->get();
        $obras2 = SicossObras::orderBy('codigo')->get();
        $zonas = SicossZona::orderBy('codigo')->get();
        $sinie = SicossSinie::orderBy('codigo')->get();
        $provincias = Sue012::orderBy('codigo')->where('codigo','!=','')->get();
        $capacidades = Sue052::orderBy('codigo')->paginate(12);
        $bancos = Fza002::orderBy('detalle')->get();
        $sicossCodBajas = SicossCodigosBaja::orderBy('detalle')->get();
        $historial = Sue074::orderBy('id')->where('legajo_codigo', '=', $empleado->codigo)->get();

        return Inertia::render('Empleados/Index', [
            'legajo' => $legajo,
            'agregar' => $agregar,
            'edicion' => $edicion,
            'active' => $active,
            'provincias' => $provincias,
            'grupos' => $grupos,
            'jerarquias' => $jerarquias,
            'categorias' => $categorias,
            'ccostos' => $ccostos,
            'sectores' => $sectores,
            'cuadrillas' => $cuadrillas,
            'obras' => $obras2,
            'sindicatos' => $sindicatos,
            'convenios' => $convenios,
            'contrataciones' => $contrataciones,
            'situacionesLab' => $situacionesLab,
        ]);
    }

    /**
     * Muestra el formulario para editar un empleado
     */
    public function edit(Sue001 $empleado)
    {
        return Inertia::render('Empleados/Form', [
            'empleado' => $empleado,
            'isEditing' => true,
        ]);
    }

    /**
     * Actualiza un empleado en la base de datos
     */
    public function update(Request $request, Sue001 $empleado)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:empleados,dni,' . $empleado->id,
            'detalle' => 'required|string|max:100',
            'nombres' => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            'sexo' => 'required|in:Masculino,Femenino,Otro',
            'email' => 'required|email|max:100|unique:empleados,email,' . $empleado->id,
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'fecha_ingreso' => 'required|date',
            'puesto' => 'required|string|max:100',
            'salario' => 'required|numeric|min:0',
            'estado' => 'required|boolean',
        ]);

        $empleado->update($validated);

        return redirect()->route('empleados.index')
            ->with('success', 'Empleado actualizado exitosamente.');
    }

    /**
     * Elimina un empleado de la base de datos
     */
    public function destroy(Sue001 $empleado)
    {
        $empleado->delete();

        return redirect()->route('empleados.index')
            ->with('success', 'Empleado eliminado exitosamente.');
    }

    // Primer registro
    public function first()
    {
        $legajo = Sue001::orderBy('id', 'asc')->first();

        if (!$legajo) {
            return redirect()->route('legajos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('legajos.show', $legajo->id);
    }

    // Último registro
    public function last()
    {
        $legajo = Sue001::orderBy('id', 'desc')->first();

        if (!$legajo) {
            return redirect()->route('legajos.index')
                ->with('error', 'No hay registros');
        }

        return redirect()->route('legajos.show', $legajo->id);
    }

    // Registro anterior
    public function previous($id)
    {
        $previousId = Sue001::where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first()?->id;

        if (!$previousId) {
            return redirect()->route('legajos.show', $id)
                ->with('warning', 'No hay registro anterior');
        }

        return redirect()->route('legajos.show', $previousId);
    }

    // Registro siguiente
    public function next($id)
    {
        $nextId = Sue001::where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first()?->id;

        if (!$nextId) {
            return redirect()->route('legajos.index', $id)
                ->with('warning', 'No hay registro siguiente');
        }

        return redirect()->route('legajos.index', $nextId);
    }

    // Búsqueda
    public function search(Request $request)
    {
        $query = Sue001::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('apellido', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('legajo_numero', 'LIKE', "%{$search}%");
            });
        }

        return Inertia::render('Legajos/Search', [
            'legajos' => $query->paginate(20),
            'filters' => $request->only('search')
        ]);
    }
}
