<?php

namespace App\Http\Controllers;

use App\Models\LsdEmision;
use App\Models\Sue086;
use App\Models\Sue090;
use App\Models\Sue100;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $tipoLiquidacion = $request->tipo_liquidacion;

            if (!$empresa || !$periodo) {
                return response()->json(['success' => false, 'message' => 'Empresa o período no encontrados'], 404);
            }

            $cuit = str_replace('-', '', $empresa->cuit ?? ''); // Obtener el CUIT de la empresa

            $periodoStr = $periodo->periodo; // Asumiendo que el campo 'periodo' tiene el formato 'YYYY/MM'

            // Generar número de emisión
            $ultimaEmision = LsdEmision::where('id_empresa', $request->id_empresa)
                ->max('numero_emision') ?? 0;
            $numeroEmision = $ultimaEmision + 1;
            
            $fileData = $this->generarTxt($empresa, $periodo, $tipoLiquidacion);

            // Si generarTxt devolvió un error (por ejemplo 404), retornarlo y no crear la emisión
            if (!is_array($fileData) || ($fileData['status'] ?? 200) !== 200) {
                $message = is_array($fileData) && isset($fileData['message']) ? $fileData['message'] : 'Error generando archivo';
                return response()->json(['success' => false, 'message' => $message], $fileData['status'] ?? 500);
            }

            // Crear nueva emisión
            $emision = LsdEmision::create([
                'id_empresa' => $request->id_empresa,
                'periodo_id' => $request->periodo_id,
                'cuit_empresa' => $cuit,
                'numero_emision' => $numeroEmision,
                'fecha_emision' => now()->toDateString(),
                'periodo' => $periodoStr,
                'cantidad_empleados' => 0,
                'monto_total' => 0,
                'estado' => 'borrador',
                'usuario_id' => auth()->id(),
                'fecha_generacion' => now(),
                'observaciones' => $request->observaciones ?? '',
                'tipo_liquidacion' => $request->tipo_liquidacion,
                'fecha_pago' => $request->fecha_pago,
            ]);

            // Después de crear la emisión, devolver el archivo para descarga
            $fullPath = $fileData['path'];
            $filename = $fileData['filename'];

            return response()->download($fullPath, $filename);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la emisión: ' . $e->getMessage() . ' en línea ' . $e->getLine(),
            ], 500);
        }
    }

    public function generarTxt($empresa, $periodo, $tipoLiquidacion)
    {
        $empresaId = $empresa->id;
        $empresaName = $empresa->detalle ?? '';
        $cuit = str_replace('-', '', $empresa->cuit ?? ''); // Obtener el CUIT de la empresa
        $periodoId = $periodo->id;
        $periodoStr = $periodo->periodo; // Asumiendo que el campo 'periodo' tiene el formato 'YYYY/MM'
        $fechaPago = $periodo->fecha_pago ?? '20260101'; // Asumiendo que el campo 'fecha_pago' existe en la tabla de periodos
        $identificadorEnvio = 'SJ';  // 'SJ'=Informa la liquidación de SyJ y datos de la DJ F931  'RE'=Sólo informa datos de la   DJ F931 para casos donde se debe rectificar sólo información de la DJ

        $tipoLiquidacion2 = 'M';    // Mes;
        if ($tipoLiquidacion == 1) {
            $tipoLiquidacion2 = 'M';
        } elseif ($tipoLiquidacion == 2) {
            $tipoLiquidacion2 = 'Q';
        } elseif ($tipoLiquidacion == 3) {
            $tipoLiquidacion2 = 'D';
        } elseif ($tipoLiquidacion == 4) {
            $tipoLiquidacion2 = 'H';
        }

        $nroLiquidacion = '00001'; // Número de liquidación dentro del período (1, 2, 3, etc.). Para este ejemplo, siempre se pone 1. En una implementación real, podrías querer contar cuántas liquidaciones ya existen para ese período y empresa y asignar el siguiente número.
        
        // Buscar todos los registros de sue090s
        $codEmpresa = $empresa->codigo ?? $empresa->id ?? null;
        
        // Buscar registros de sue090s solo para legajos cuyo grupo_emp en sue001s coincide con $codEmpresa
        $query = DB::table('sue090s')
            ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
            ->where('sue090s.periodo', $periodoStr);

        if ($codEmpresa !== null && $codEmpresa !== '') {
            $query->where('sue001s.grupo_emp', $codEmpresa);
        }

        $datos = $query->select('sue090s.*', 'sue001s.cuil as cuil', 
            'sue001s.codigo as legajo_codigo',
            'sue001s.sicoss_conyuge as conyugue',
            'sue001s.sicoss_hijos as hijos')->get();

        // Debug: registrar información no intrusiva sobre $datos
        try {
            Log::debug('LSD datos count: ' . $datos->count());
            $sample = array_slice($datos->toArray(), 0, 20);
            Log::debug('LSD datos sample: ' . json_encode($sample, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // no interrumpir la ejecución por fallos en el logging
        }

        if ($datos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron datos'], 404);
        }

        $diasDePeriodo = '30'; // Si “identificación del envío” es igual a “RE”, dejar en blanco 
        $cantidadEmpleados = $datos->count(); // Debe coincidir con la cantidad de registros tipo ‘04’ informados en el archivo. Coincide con la cantidad de empleados del F931
        $montoTotal = $datos->sum('importe'); // Asumiendo que hay

        // Si es una rectificativa, se ajustan varios campos y no se informa el tipo de liquidación
        if ($identificadorEnvio == 'RE') {
            $tipoLiquidacion2 = ' '; // En caso de rectificativa, el tipo de liquidación no se informa
            $diasDePeriodo = '  ';
        }
        
        //---------------------------------------
        // Generar Registro de Encabezado (Tipo 01)
        //---------------------------------------
        $contenido = '01';
        
        // Registro 1: Encabezado
        $contenido .= $cuit . $identificadorEnvio . $periodoStr . $tipoLiquidacion2 . $nroLiquidacion . 
            $diasDePeriodo . str_pad($cantidadEmpleados, 6, '0', STR_PAD_LEFT);
        
        // str_pad(number_format($montoTotal, 2, '', ''), 15, '0', STR_PAD_LEFT) . $diasDePeriodo . "\n"
        
        // FÓRMULA CONTROL DE LARGO DEL CAMPO 35
        if (strlen($contenido) != 35) {

            return [
                'status' => 500,
                'message' => ' en el formato del registro de encabezado. El largo debe ser exactamente 35 caracteres, se registró un largo de ' . strlen($contenido) . ' caracteres. ' . 
                'Contenido generado: "' . $contenido . '"'
                
            ];

        }

        //$contenido .= "EMPRESA: {$empresaId} | PERIODO: {$periodoId}\n";
        //$contenido .= str_repeat('-', 80) . "\n";
        $contenido .= "\n";

        //---------------------------------------
        // Generar Registro del Cuerpo (Tipo 02)
        //---------------------------------------
        $diasTope = '  0'; // Cant. de días para proporcionar el tope:  Este valor se utiliza para proporcionar en más o en menos la base imponible máxima o "tope" para el cálculo de los descuentos de aportes al trabajador (SIPA, INSSJyP y obra social/Fondo Solidario de Redistribución). Si la liquidación no corresponde a periodo de inicio o fin de la relación laboral o relacionada con vacaciones,  este valor debe informarse en 0.
        //$fechaPago = $fechaPago ?? '20260101'; // Fecha de pago en formato YYYYMMDD
        $fechaRubrica = '        ';  // No se completa por el momento
        $formaPago = '1'; // Forma de pago: 1= efectivo , 2= cheque 3= acreditación
        
        // Datos (ajusta los campos según tu tabla sue090s)
        // Agrupar por cuil y tomar solo un registro por cuil para este bloque
        $datosPorCuil = $datos->unique('cuil');

        foreach ($datosPorCuil as $registro) {
                $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_LEFT);
                $cuilValue = $registro->cuil ?? '';
                $dependencia = str_pad($registro->dependenciaRevista ?? '', 50, ' ', STR_PAD_LEFT);
                $cbu = $registro->cbu ?? str_repeat(' ', 22);

                $line02 = '02'
                    . $cuilValue
                    . $legajoValue
                    . $dependencia
                    . $cbu
                    . $diasTope
                    . $fechaPago
                    . $fechaRubrica
                    . $formaPago;

                $contenido .= $line02 . "\n";
        }
        
        //---------------------------------------
        // Generar Registro Tipo 03 - Detalle de los conceptos liquidados a cada trabajador
        //---------------------------------------
        foreach ($datos as $registro) {
                $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_LEFT);
                $cuilValue = $registro->cuil ?? '';
                $concepto = str_pad($registro->concepto ?? '', 10, ' ', STR_PAD_LEFT);
                $cantidad = str_pad($registro->cantidad ?? 0, 6, '$', STR_PAD_LEFT);    
                $unidades = str_pad($registro->unidades ?? '', 10, ' ', STR_PAD_LEFT);      // $=moneda; %=porcentuales;   A=año; Q=quincena; M=mes;   D=días; H=horas.  Valor optativo, puede informarse en blanco.
                $importe = str_pad(number_format($registro->importe ?? 0, 2, '', ''), 15, '0', STR_PAD_LEFT); // Sin decimales, sin separadores de miles, con ceros a la izquierda. Ejemplo: $1234.56 se informaría como 0000000000123456
                $debitoCredito = 'D'; // D=Débito (descuento); C=Crédito (remunerativo)
                $periodoAjuste = '        ';
                
                $line03 = '03'
                    . $cuilValue
                    . $concepto 
                    . $cantidad
                    . $unidades
                    . $importe
                    . $debitoCredito
                    . $periodoAjuste;

                $contenido .= $line03 . "\n";
        }

        //---------------------------------------
        // Generar Registro Tipo 04 - Atributos de la relación laboral - DJ - Una fila por cada empleado propio o eventual que se deba declarar en el F931
        //---------------------------------------
        foreach ($datos as $registro) {
                $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_LEFT);
                $cuilValue = $registro->cuil ?? '';
                $conyugue = str_pad($registro->conyugue ?? '', 1, ' ', STR_PAD_LEFT); // 0 = NO  1= SI
                $hijos = str_pad($registro->hijos ?? 0, 2, '0', STR_PAD_LEFT); // Cantidad de hijos menores de 18 años o incapacitados para el trabajo
                $cct = str_pad($registro->cct ?? '0', 1, '0', STR_PAD_LEFT); // Convenio Colectivo de Trabajo. Valor optativo, puede informarse en blanco.  
                $scvo = str_pad($registro->scvo ?? '0', 1, '0', STR_PAD_LEFT); // Sí/No de trabajador con S/CV. Valor optativo, puede informarse en blanco.
                $reduccion = str_pad($registro->reduccion ?? '0', 1, '0', STR_PAD_LEFT); // Sí/No de reducción de jornada por cuidado de hijes menores de 12 años. Valor optativo, puede informarse en blanco.
                $tipoempresa = "1"; // str_pad($registro->tipoempresa ?? '0', 1, '0', STR_PAD_LEFT); // Tipo de empresa: 0 - Administración Pública  1-Decreto 814/01, Art2 Inc.B 2-Servicios Eventuales, Art2 Inc.B 4-Decreto 814/01, Art2 Inc.A 5-Servicios Eventuales, Art2 Inc.A 7-Enseñanza Privada 8-Decreto 1212/03 - AFA Clubes
                $tipoOperacion = "0";
                $situacion = str_pad($registro->codigoSituacion ?? '0', 1, '0', STR_PAD_LEFT); // Código de situación del trabajador: 0-Activo; 1-Baja; 2-Vacaciones; 3-Licencia por enfermedad; 4-Licencia por maternidad/paternidad; 5-Reducción de jornada por cuidado de hijes menores de 12 años; 6-Suspensión por falta o reducción de tareas; 7-Suspensión por fuerza mayor; 8-Embarazo; 9-Otra licencia
                $condicion = str_pad($registro->codigoCondicion ?? '0', 1, '0', STR_PAD_LEFT); // Código de condición del trabajador: 0-Empleado mensualizado; 1-Empleado jornalizado; 2-Empleado eventual; 3-Empleado doméstico; 4-Contratista; 5-Monotributista; 6-Honorarios; 7-Servicio de locación; 8-Servicio de comisión; 9-Otra condición
                $actividad = str_pad($registro->actividad ?? '49', 3, ' ', STR_PAD_LEFT); // Código de actividad. Valor optativo, puede informarse en blanco.
                $modalidadContrato = str_pad($registro->modalidadContrato ?? '8', 3, '0', STR_PAD_LEFT); // Modalidad de contrato: 0-Contrato a plazo fijo; 1-Contrato por tiempo indeterminado; 2-Contrato de temporada; 3-Contrato eventual; 4-Contrato de aprendizaje; 5-Contrato de pasantía; 6-Contrato de trabajo a domicilio; 7-Contrato de teletrabajo; 8-Otra modalidad
                $siniestro = str_pad($registro->siniestro ?? '0', 2, '0', STR_PAD_LEFT); // Sí/No de trabajador siniestrado. Valor optativo, puede informarse en blanco.
                $localidad = str_pad($registro->localidad ?? '', 2, ' ', STR_PAD_LEFT); // Localidad del trabajador. Valor optativo, puede informarse en blanco.
                $situacionRevista1 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista1 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $situacionRevista2 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista2 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $situacionRevista3 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista3 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $cantidadDias = str_pad($registro->cantidadDias ?? '30', 2, '0', STR_PAD_LEFT); // Cantidad de días trabajados en el período. Valor optativo, puede informarse en blanco.
                $cantidadHoras = str_pad($registro->cantidadHoras ?? '0', 3, '0', STR_PAD_LEFT); // Si se informa un valor, el campo Cantidad días trabajados debe ser 0. Formato: 3 dígitos enteros.
                $porcAporteAdicionalSS = str_pad($registro->porcAporteAdicionalSS ?? '0', 5, '0', STR_PAD_LEFT); // Se consignarán los puntos porcentuales que superen los establecidos en la Ley N° 24241, artículo 11 o Decreto N° 1387/01, artículo 15. El programa adicionará el porcentaje adicional que se consigne en el campo al aporte obligatorio vigente a cada periodo y procederá al cálculo sobre la Base Imponible de aportes SIPA. 
                $contribucionTareDif = str_pad($registro->contribucionTareDif ?? '0', 5, '0', STR_PAD_LEFT); // Refleja el cálculo de los aportes diferenciales sobre la Base Imponible de Regímenes Diferenciales (por ejemplo: 2% aporte diferencial de los docentes) 
                $codObraSocial = str_pad($registro->codigoObraSocial ?? '', 6, ' ', STR_PAD_LEFT); // Código de obra social. Valor optativo, puede informarse en blanco.
                $adherentes = "0";  // Se registra el número de aquellos que no integran el grupo familiar. Ese dato es tenido en cuenta para el incremento del porcentaje a considerar para el cálculo de aportes de Obra Social.
                $aporteAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán los aportes del trabajador, emergentes de la diferencia entre la remuneración efectivamente percibida por este y el mínimo fijado por ANSES, a los efectos de acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales
                $contribAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán las contribuciones del empleador, emergentes de la diferencia entre la remuneración efectivamente percibida por el trabajador y el mínimo fijado por ANSES, a los efectos de permitirle a este acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialAportes = str_pad($registro->baseCalculoDiferencialAportes ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 4 (aportes de obra social y FSR) en los casos de trabajadores a tiempo parcial que aportan como tiempo completo (Ley, 26.474 art 1, inc. 4) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialOS = str_pad($registro->baseCalculoDiferencialOs ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 8 (contribuciones de obra social y FSR) en los casos de trabajadores a tiempo parcial que contribuyen como tiempo completo (Ley, 26.474 art 1, inc. 4) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialLRT = str_pad($registro->baseCalculoDiferencialLRT ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 9 (contribuciones LRT) . Formato: 13 dígitos enteros y 2 decimales. 
                $remuneracionMaternidad = str_pad($registro->remuneracionMaternidad ?? '0', 15, '0', STR_PAD_LEFT); // Informará el monto de la remuneración bruta que le hubiera correspondido percibir a la trabajadora si hubiera cumplido sus servicios normalmente.  Formato: 13 dígitos enteros y 2 decimales. 
                $remuneracionBruta = str_pad($registro->remuneracionBruta ?? '0', 15, '0', STR_PAD_LEFT); // Es la suma de los conceptos remunerativos y no remunerativos liquidados en el mes. Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible1 = str_pad($registro->baseImponible1 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes Previsionales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible2 = str_pad($registro->baseImponible2 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Contribuciones previsionales e INSSJyP Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible3 = str_pad($registro->baseImponible3 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para  Contribuciones FNE, asignaciones familiares y RENATRE Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible4 = str_pad($registro->baseImponible4 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes obra social y FSR Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible5 = str_pad($registro->baseImponible5 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes INSSJyP Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible6 = str_pad($registro->baseImponible6 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes diferenciales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible7 = str_pad($registro->baseImponible7 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes personal regímenes especiales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible8 = str_pad($registro->baseImponible8 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Contribuciones obra social y FSR Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible9 = str_pad($registro->baseImponible9 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Ley de riesgos del trabajo Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialAportesSS = str_pad($registro->baseCalculoDiferencialAportesSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 1 Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialContribSS = str_pad($registro->baseCalculoDiferencialContribSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 2 Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible10 = str_pad($registro->baseImponible10 ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferencia en REM2 y el importe a detraer establecido  por la ley 27430 Formato: 13 dígitos enteros y 2 decimales. 
                $importeDetraer = str_pad($registro->importeDetraer ?? '0', 15, '0', STR_PAD_LEFT); // Para informar el importe a detraer establecido por la ley 27430 Formato: 13 dígitos enteros y 2 decimales. 
                
                $line04 = '04'
                    . $cuilValue
                    . $conyugue 
                    . $hijos
                    . $cct
                    . $scvo
                    . $reduccion
                    . $tipoempresa
                    . $tipoOperacion
                    . $situacion
                    . $condicion
                    . $actividad
                    . $modalidadContrato
                    . $siniestro
                    . $localidad
                    . $situacionRevista1
                    . $diaSituacionRevista1
                    . $situacionRevista2
                    . $diaSituacionRevista2
                    . $situacionRevista3
                    . $diaSituacionRevista3
                    . $cantidadDias
                    . $cantidadHoras
                    . $porcAporteAdicionalSS
                    . $contribucionTareDif
                    . $codObraSocial
                    . $adherentes
                    . $aporteAdicionalOS
                    . $contribAdicionalOS
                    . $baseCalculoDiferencialAportes
                    . $baseCalculoDiferencialOS
                    . $baseCalculoDiferencialLRT
                    . $remuneracionMaternidad
                    . $remuneracionBruta
                    . $baseImponible1
                    . $baseImponible2
                    . $baseImponible3
                    . $baseImponible4
                    . $baseImponible5
                    . $baseImponible6
                    . $baseImponible7
                    . $baseImponible8
                    . $baseImponible9
                    . $baseCalculoDiferencialAportesSS
                    . $baseCalculoDiferencialContribSS
                    . $baseImponible10;

                $contenido .= $line04 . "\n";
        }

        
        //---------------------------------------
        // Generar Registro Tipo 05 - Trabajadores Eventuales - Una fila por cada empleado declarado con modalidad 102 en el registro 4
        //---------------------------------------
        foreach ($datos as $registro) {
                $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_LEFT);
                $cuilValue = $registro->cuil ?? '';
                $conyugue = str_pad($registro->conyugue ?? '', 1, ' ', STR_PAD_LEFT); // 0 = NO  1= SI
                $hijos = str_pad($registro->hijos ?? 0, 2, '0', STR_PAD_LEFT); // Cantidad de hijos menores de 18 años o incapacitados para el trabajo
                $cct = str_pad($registro->cct ?? '0', 1, '0', STR_PAD_LEFT); // Convenio Colectivo de Trabajo. Valor optativo, puede informarse en blanco.  
                $scvo = str_pad($registro->scvo ?? '0', 1, '0', STR_PAD_LEFT); // Sí/No de trabajador con S/CV. Valor optativo, puede informarse en blanco.
                $reduccion = str_pad($registro->reduccion ?? '0', 1, '0', STR_PAD_LEFT); // Sí/No de reducción de jornada por cuidado de hijes menores de 12 años. Valor optativo, puede informarse en blanco.
                $tipoempresa = "1"; // str_pad($registro->tipoempresa ?? '0', 1, '0', STR_PAD_LEFT); // Tipo de empresa: 0 - Administración Pública  1-Decreto 814/01, Art2 Inc.B 2-Servicios Eventuales, Art2 Inc.B 4-Decreto 814/01, Art2 Inc.A 5-Servicios Eventuales, Art2 Inc.A 7-Enseñanza Privada 8-Decreto 1212/03 - AFA Clubes
                $tipoOperacion = "0";
                $situacion = str_pad($registro->codigoSituacion ?? '0', 1, '0', STR_PAD_LEFT); // Código de situación del trabajador: 0-Activo; 1-Baja; 2-Vacaciones; 3-Licencia por enfermedad; 4-Licencia por maternidad/paternidad; 5-Reducción de jornada por cuidado de hijes menores de 12 años; 6-Suspensión por falta o reducción de tareas; 7-Suspensión por fuerza mayor; 8-Embarazo; 9-Otra licencia
                $condicion = str_pad($registro->codigoCondicion ?? '0', 1, '0', STR_PAD_LEFT); // Código de condición del trabajador: 0-Empleado mensualizado; 1-Empleado jornalizado; 2-Empleado eventual; 3-Empleado doméstico; 4-Contratista; 5-Monotributista; 6-Honorarios; 7-Servicio de locación; 8-Servicio de comisión; 9-Otra condición
                $actividad = str_pad($registro->actividad ?? '49', 3, ' ', STR_PAD_LEFT); // Código de actividad. Valor optativo, puede informarse en blanco.
                $modalidadContrato = str_pad($registro->modalidadContrato ?? '8', 3, '0', STR_PAD_LEFT); // Modalidad de contrato: 0-Contrato a plazo fijo; 1-Contrato por tiempo indeterminado; 2-Contrato de temporada; 3-Contrato eventual; 4-Contrato de aprendizaje; 5-Contrato de pasantía; 6-Contrato de trabajo a domicilio; 7-Contrato de teletrabajo; 8-Otra modalidad
                $siniestro = str_pad($registro->siniestro ?? '0', 2, '0', STR_PAD_LEFT); // Sí/No de trabajador siniestrado. Valor optativo, puede informarse en blanco.
                $localidad = str_pad($registro->localidad ?? '', 2, ' ', STR_PAD_LEFT); // Localidad del trabajador. Valor optativo, puede informarse en blanco.
                $situacionRevista1 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista1 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $situacionRevista2 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista2 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $situacionRevista3 = str_pad($registro->situacionRevista ?? '0', 2, '0', STR_PAD_LEFT); // Situación de revista: 0-Propio; 1-Eventual; 2-Contratista; 3-Monotributista; 4-Honorarios; 5-Servicio de locación; 6-Servicio de comisión; 7-Otra condición
                $diaSituacionRevista3 = " 1"; // Día del mes en que se produce el cambio de situación de revista. Solo se informa si el campo "Situación de revista" es distinto de 0 (Propio). En caso de corresponder, informar con ceros a la izquierda (Ejemplo: 01, 15, 30, etc.). Valor optativo, puede informarse en blanco.
                $cantidadDias = str_pad($registro->cantidadDias ?? '30', 2, '0', STR_PAD_LEFT); // Cantidad de días trabajados en el período. Valor optativo, puede informarse en blanco.
                $cantidadHoras = str_pad($registro->cantidadHoras ?? '0', 3, '0', STR_PAD_LEFT); // Si se informa un valor, el campo Cantidad días trabajados debe ser 0. Formato: 3 dígitos enteros.
                $porcAporteAdicionalSS = str_pad($registro->porcAporteAdicionalSS ?? '0', 5, '0', STR_PAD_LEFT); // Se consignarán los puntos porcentuales que superen los establecidos en la Ley N° 24241, artículo 11 o Decreto N° 1387/01, artículo 15. El programa adicionará el porcentaje adicional que se consigne en el campo al aporte obligatorio vigente a cada periodo y procederá al cálculo sobre la Base Imponible de aportes SIPA. 
                $contribucionTareDif = str_pad($registro->contribucionTareDif ?? '0', 5, '0', STR_PAD_LEFT); // Refleja el cálculo de los aportes diferenciales sobre la Base Imponible de Regímenes Diferenciales (por ejemplo: 2% aporte diferencial de los docentes) 
                $codObraSocial = str_pad($registro->codigoObraSocial ?? '', 6, ' ', STR_PAD_LEFT); // Código de obra social. Valor optativo, puede informarse en blanco.
                $adherentes = "0";  // Se registra el número de aquellos que no integran el grupo familiar. Ese dato es tenido en cuenta para el incremento del porcentaje a considerar para el cálculo de aportes de Obra Social.
                $aporteAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán los aportes del trabajador, emergentes de la diferencia entre la remuneración efectivamente percibida por este y el mínimo fijado por ANSES, a los efectos de acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales
                $contribAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán las contribuciones del empleador, emergentes de la diferencia entre la remuneración efectivamente percibida por el trabajador y el mínimo fijado por ANSES, a los efectos de permitirle a este acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialAportes = str_pad($registro->baseCalculoDiferencialAportes ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 4 (aportes de obra social y FSR) en los casos de trabajadores a tiempo parcial que aportan como tiempo completo (Ley, 26.474 art 1, inc. 4) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialOS = str_pad($registro->baseCalculoDiferencialOs ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 8 (contribuciones de obra social y FSR) en los casos de trabajadores a tiempo parcial que contribuyen como tiempo completo (Ley, 26.474 art 1, inc. 4) Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialLRT = str_pad($registro->baseCalculoDiferencialLRT ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 9 (contribuciones LRT) . Formato: 13 dígitos enteros y 2 decimales. 
                $remuneracionMaternidad = str_pad($registro->remuneracionMaternidad ?? '0', 15, '0', STR_PAD_LEFT); // Informará el monto de la remuneración bruta que le hubiera correspondido percibir a la trabajadora si hubiera cumplido sus servicios normalmente.  Formato: 13 dígitos enteros y 2 decimales. 
                $remuneracionBruta = str_pad($registro->remuneracionBruta ?? '0', 15, '0', STR_PAD_LEFT); // Es la suma de los conceptos remunerativos y no remunerativos liquidados en el mes. Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible1 = str_pad($registro->baseImponible1 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes Previsionales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible2 = str_pad($registro->baseImponible2 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Contribuciones previsionales e INSSJyP Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible3 = str_pad($registro->baseImponible3 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para  Contribuciones FNE, asignaciones familiares y RENATRE Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible4 = str_pad($registro->baseImponible4 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes obra social y FSR Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible5 = str_pad($registro->baseImponible5 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes INSSJyP Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible6 = str_pad($registro->baseImponible6 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes diferenciales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible7 = str_pad($registro->baseImponible7 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Aportes personal regímenes especiales Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible8 = str_pad($registro->baseImponible8 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Contribuciones obra social y FSR Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible9 = str_pad($registro->baseImponible9 ?? '0', 15, '0', STR_PAD_LEFT); // Base de cálculo para Ley de riesgos del trabajo Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialAportesSS = str_pad($registro->baseCalculoDiferencialAportesSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 1 Formato: 13 dígitos enteros y 2 decimales. 
                $baseCalculoDiferencialContribSS = str_pad($registro->baseCalculoDiferencialContribSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 2 Formato: 13 dígitos enteros y 2 decimales. 
                $baseImponible10 = str_pad($registro->baseImponible10 ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferencia en REM2 y el importe a detraer establecido  por la ley 27430 Formato: 13 dígitos enteros y 2 decimales. 
                $importeDetraer = str_pad($registro->importeDetraer ?? '0', 15, '0', STR_PAD_LEFT); // Para informar el importe a detraer establecido por la ley 27430 Formato: 13 dígitos enteros y 2 decimales. 
                
                if ($modalidadContrato == '102') {
                    $line05 = '05'
                        . $cuilValue
                        . $conyugue 
                        . $hijos
                        . $cct
                        . $scvo
                        . $reduccion
                        . $tipoempresa
                        . $tipoOperacion
                        . $situacion
                        . $condicion
                        . $actividad
                        . $modalidadContrato
                        . $siniestro
                        . $localidad
                        . $situacionRevista1
                        . $diaSituacionRevista1
                        . $situacionRevista2
                        . $diaSituacionRevista2
                        . $situacionRevista3
                        . $diaSituacionRevista3
                        . $cantidadDias
                        . $cantidadHoras
                        . $porcAporteAdicionalSS
                        . $contribucionTareDif
                        . $codObraSocial
                        . $adherentes
                        . $aporteAdicionalOS
                        . $contribAdicionalOS
                        . $baseCalculoDiferencialAportes
                        . $baseCalculoDiferencialOS
                        . $baseCalculoDiferencialLRT
                        . $remuneracionMaternidad
                        . $remuneracionBruta
                        . $baseImponible1
                        . $baseImponible2
                        . $baseImponible3
                        . $baseImponible4
                        . $baseImponible5
                        . $baseImponible6
                        . $baseImponible7
                        . $baseImponible8
                        . $baseImponible9
                        . $baseCalculoDiferencialAportesSS
                        . $baseCalculoDiferencialContribSS
                        . $baseImponible10;

                    $contenido .= $line05 . "\n";
                }
        }

        //$contenido .= str_repeat('-', 80) . "\n";
        //$contenido .= "TOTAL REGISTROS: " . $datos->count() . "\n";
        
        // Guardar en storage/app/lsd y devolver información para descarga
        $dir = storage_path('app/lsd');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "LSD_{$empresaName}_{$empresaId}_periodo_{$periodoId}_" . date('Ymd_His') . ".txt";
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            file_put_contents($fullPath, $contenido);
        } catch (\Throwable $e) {
            return [
                'status' => 500,
                'message' => 'No se pudo guardar el archivo: ' . $e->getMessage() . ' en línea ' . $e->getLine(),
                'line' => $e->getLine(),
            ];
        }

        return ['status' => 200, 'path' => $fullPath, 'filename' => $filename];
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
