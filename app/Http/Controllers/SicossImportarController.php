<?php

namespace App\Http\Controllers;

use App\Models\Sicoss01;    // Actividades sicoss
use App\Models\Sicoss05;    // Condiciones sicoss
use App\Models\Sicoss08;    // Modalidades sicoss
use App\Models\Sicoss12;    // Situaciones sicoss
use App\Models\SicossObras;
use App\Models\Sue001;      // Empleados activos
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
        //$zonas = SicossZona::orderBy('codigo')->get();
        //$sinie = SicossSinie::orderBy('codigo')->get();

        return view('sicoss.importar')->with(compact(
            'legajo', 'active', 'agregar', 'edicion', 'actividades', 'condiciones', 'contrataciones', 'periodo2', 'user', 'rol'
        ));
    }

    public function importar(Request $request)
    {
        $empresa = Datoempr::first();
        if (!$empresa) return redirect('/empresa/');

        return Inertia::render('Sicoss/Importar', [
            'empresa' => $empresa,
        ]);
    }
}
