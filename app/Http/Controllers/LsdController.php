<?php

namespace App\Http\Controllers;

use App\Models\LsdEmision;
use App\Models\Sue086;
use App\Models\Sue090;
use App\Models\Sue100;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LsdController extends Controller
{
    /**
     * Mostrar la página para generar LSD
     */
    public function generar()
    {
        $empresas = Sue086::orderBy('codigo')->get();
        $periodos = Sue100::orderBy('periodo', 'desc')->get();
        $emisiones = LsdEmision::orderBy('created_at', 'desc')->limit(10)->get();
        
        return Inertia::render('Lsd/Generar', [
            'empresas' => $empresas,
            'periodos' => $periodos,
            'emisiones' => $emisiones,
        ]);
    }

    /**
     * Generar nueva emisión de LSD
     */
    public function generarEmision(Request $request)
    {
        $request->validate([
            'id_empresa' => 'required|exists:sue086s,id',
            'periodo_id' => 'required|exists:sue100s,id',
            'tipo_liquidacion' => 'required|in:1,2,3,4',
            'fecha_pago' => 'required|date',
        ]);

        try {
            // Obtener datos de la empresa
            $empresa = Sue086::find($request->id_empresa);
            $periodo = Sue100::find($request->periodo_id);
            
            // Generar número de emisión
            $ultimaEmision = LsdEmision::where('id_empresa', $request->id_empresa)
                ->max('numero_emision') ?? 0;
            $numeroEmision = $ultimaEmision + 1;
            
            This->generarTxt($empresa, $periodo);

            // Crear nueva emisión
            $emision = LsdEmision::create([
                'id_empresa' => $request->id_empresa,
                'numero_emision' => $numeroEmision,
                'fecha_emision' => now()->toDateString(),
                'periodo_desde' => $periodo->fecha,
                'periodo_hasta' => $periodo->fecha_pago,
                'cantidad_empleados' => 0,
                'monto_total' => 0,
                'estado' => 'borrador',
                'usuario_id' => auth()->id(),
                'observaciones' => $request->observaciones ?? '',
                'tipo_liquidacion' => $request->tipo_liquidacion,
                'fecha_pago' => $request->fecha_pago,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Emisión de LSD creada exitosamente',
                'emision' => $emision,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la emisión: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generarTxt($empresa, $periodo)
    {
        $empresaId = $empresa->id;
        $periodoId = $periodo->id;
        
        // Buscar todos los registros de sue090s
        $datos = DB::table('sue090s')
            ->where('id_empresa', $empresaId)
            ->where('periodo_id', $periodoId)
            ->get();
        
        if ($datos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron datos'], 404);
        }
        
        // Generar contenido del archivo TXT
        $contenido = '';
        
        // Encabezado (ajusta según tus necesidades)
        $contenido .= "EMPRESA: {$empresaId} | PERIODO: {$periodoId}\n";
        $contenido .= str_repeat('-', 80) . "\n";
        
        // Datos (ajusta los campos según tu tabla sue090s)
        foreach ($datos as $registro) {
            $contenido .= sprintf(
                "LEGAJO: %s | EMPLEADO: %s | CONCEPTO: %s | IMPORTE: %s\n",
                $registro->legajo ?? '',
                $registro->nombre ?? '',
                $registro->concepto ?? '',
                number_format($registro->importe ?? 0, 2, ',', '.')
            );
        }
        
        $contenido .= str_repeat('-', 80) . "\n";
        $contenido .= "TOTAL REGISTROS: " . $datos->count() . "\n";
        
        // Retornar como archivo descargable
        $filename = "sue090s_empresa_{$empresaId}_periodo_{$periodoId}.txt";
        
        return response($contenido, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }


    /**
     * Obtener detalles de una emisión
     */
    public function obtenerEmision($id)
    {
        $emision = LsdEmision::with(['empresa', 'usuario'])->find($id);

        if (!$emision) {
            return response()->json(['error' => 'Emisión no encontrada'], 404);
        }

        return response()->json($emision);
    }

    /**
     * Actualizar estado de emisión
     */
    public function actualizarEstado($id, Request $request)
    {
        $request->validate([
            'estado' => 'required|in:borrador,generado,enviado,confirmado,rechazado',
        ]);

        $emision = LsdEmision::find($id);

        if (!$emision) {
            return response()->json(['error' => 'Emisión no encontrada'], 404);
        }

        $emision->update([
            'estado' => $request->estado,
            'fecha_envio' => $request->estado === 'enviado' ? now() : $emision->fecha_envio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'emision' => $emision,
        ]);
    }

    /**
     * Listar emisiones
     */
    public function listar(Request $request)
    {
        $query = LsdEmision::query();

        if ($request->id_empresa) {
            $query->where('id_empresa', $request->id_empresa);
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        $emisiones = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($emisiones);
    }

    /**
     * Eliminar emisión
     */
    public function eliminar($id)
    {
        $emision = LsdEmision::find($id);

        if (!$emision) {
            return response()->json(['error' => 'Emisión no encontrada'], 404);
        }

        if ($emision->estado !== 'borrador') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar emisiones en estado borrador',
            ], 422);
        }

        $emision->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emisión eliminada exitosamente',
        ]);
    }
}
