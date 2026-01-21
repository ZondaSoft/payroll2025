<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Auth;
use App\Models\User;

class MainController extends Controller
{
    public function index($id = null, $direction = null)
    {
        $agregar = False;
        $edicion = False;    // True: Muestra botones Grabar - Cancelar   //  False: Muestra botones: Agregar, Editar, Borrar
        $active = 1;
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

        // Excepciones para usuarios con solo un permiso
        if (auth()->user()->getAllPermissions()->count() == 1) {
            if (auth()->user()->can('servicios.novedadest')) {
                //return redirect('/novedadest/add2');
            }
        }

        return view('main.index')->with(compact(
            'active','user','rol'
        ));
    }
}
