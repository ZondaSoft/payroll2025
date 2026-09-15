<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\CasosDeUso\VerificarIntegridad;
use Inertia\Inertia;
use Inertia\Response;

class IntegridadController extends Controller
{
    public function index(VerificarIntegridad $caso): Response
    {
        return Inertia::render('Motor/Integridad/Index', ['reporte' => $caso->ejecutar()->aArray()]);
    }
}
