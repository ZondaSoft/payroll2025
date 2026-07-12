<?php

namespace App\Http\Controllers;

use App\Models\LsdEmision;
use App\Models\LsdImporteDetraer;
use App\Models\LsdTope;
use App\Models\LsdItem;
use App\Models\LiquidacionCorreccion;
use App\Models\Sue086;
use App\Models\Sue100;
use App\Models\Sue102;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LsdController extends Controller
{
    /**
     * Modalidades de contratación (sicoss08s.codigo) que NO admiten la detracción del Dto 814/01
     * (art. 4, Ley 27.430), según Guía N°17 de ARCA: no aportan a SIPA, por lo que no hay base
     * para detraer. Para estas modalidades, ARCA exige importe a detraer = 0 Y Base Imponible 10 = 0
     * (de lo contrario rechaza con "No corresponde informar importe a detraer").
     *
     *   2  Becarios – Residencias médicas Ley 22.127
     *   10 Pasantías – sin obra social
     *   27 Pasantías Ley 26.427 – con obra social
     *   48 Art. 4° L. 24.241 – Traslado temporario desde el exterior / Conv. bilaterales
     *   49 Directores SA – con OS y LRT (aporta solo a OS y LRT, no a SIPA)
     *   51 Pasantías Ley 26.427 – con OS – beneficiario pensión discapacidad
     *   63 Acciones de Entrenamiento para el Trabajo – Res. 1107/2022
     *   99 LRT (Directores SA / sector público / docentes / vínculos LRT-only)
     *
     * Además, todo el rango 9xx (900-999 = Convenios de Corresponsabilidad Gremial / CCG)
     * tampoco admite la detracción — se contempla por rango en modalidadSinDetraccion().
     */
    private const MODALIDADES_SIN_DETRACCION = [2, 10, 27, 48, 49, 51, 63, 99];

    /**
     * Indica si una modalidad de contratación no admite la detracción del Dto 814/01
     * (importe a detraer = 0 y BI 10 = 0). Cubre la lista fija + el rango CCG (9xx).
     */
    private function modalidadSinDetraccion($modal): bool
    {
        $m = (int) $modal;

        // Convenios de Corresponsabilidad Gremial (CCG): rango 900-999.
        if ($m >= 900 && $m <= 999) {
            return true;
        }

        return in_array($m, self::MODALIDADES_SIN_DETRACCION, true);
    }

    /**
     * Primer día del período como 'YYYY-MM-01', para comparar contra sue001s.baja.
     * sue100s.periodo viene como 'YYYYMM' (ej. '202606'); también se tolera 'YYYY/MM'.
     */
    private function inicioPeriodo(string $periodoStr): string
    {
        $limpio = preg_replace('/\D/', '', trim($periodoStr)); // deja solo dígitos → 'YYYYMM'
        $anio = (int) substr($limpio, 0, 4);
        $mes = (int) substr($limpio, 4, 2);
        if ($mes < 1 || $mes > 12) {
            $mes = 1;
        }
        return sprintf('%04d-%02d-01', $anio ?: (int) date('Y'), $mes);
    }

    /**
     * Normaliza el filtro de tipo de liquidación del request ('1'/'4'/'5'/'todas') a un
     * array de tipoliq para whereIn, o null cuando no hay que filtrar (Todas = TXT global).
     * No confundir con tipo_liquidacion (código M/Q/D/H del Reg 01).
     */
    private function normalizarTiposLiq(?string $valor): ?array
    {
        return ($valor === null || $valor === '' || $valor === 'todas') ? null : [(int) $valor];
    }

    /**
     * Mostrar la página para generar LSD
     */
    public function generar()
    {
        // Corrige conceptos con tipo numérico legacy según los Rangos de conceptos (sue089s).
        $ajustesTipos = Sue102::normalizarTiposNumericos();

        $empresas = Sue086::orderBy('codigo')->get();
        $periodos = Sue100::select('periodo')->distinct()->orderBy('periodo', 'desc')->get();
        $emisiones = LsdEmision::orderBy('created_at', 'desc')->limit(10)->get();

        // Períodos (YYYYMM) que tienen algún tope SIPA cargado. El front avisa si el período
        // seleccionado no tiene un tope vigente (ningún periodo_desde <= período).
        $topesPeriodos = LsdTope::orderBy('periodo_desde')->pluck('periodo_desde');

        return Inertia::render('Lsd/Generar', [
            'empresas' => $empresas,
            'periodos' => $periodos,
            'emisiones' => $emisiones,
            'ajustesTipos' => $ajustesTipos,
            'topesPeriodos' => $topesPeriodos,
        ]);
    }

    /**
     * Generar nueva emisión de LSD
     */
    public function generarEmision(Request $request)
    {
        // Validación condicional: en modo 'RE' (rectificativa de DJ) ARCA exige que el TXT NO lleve
        // tipo_liquidacion ni días base. Por eso esos campos pasan a ser opcionales.
        $request->validate([
            'id_empresa' => 'required|exists:sue086s,id',
            'periodo_id' => 'required|exists:sue100s,periodo',
            'identificador_envio' => 'required|in:SJ,RE',
            'tipo_liquidacion' => 'required_if:identificador_envio,SJ|nullable|in:1,2,3,4',
            'fecha_pago' => 'required_if:identificador_envio,SJ|nullable|date',
            'tipos_liq' => 'nullable|in:1,4,5,todas',
        ], [
            'identificador_envio.required' => 'Seleccione el tipo de envío (SJ o RE).',
            'identificador_envio.in' => 'Tipo de envío inválido. Debe ser SJ o RE.',
            'tipo_liquidacion.required_if' => 'El tipo de liquidación es obligatorio para envíos SJ.',
            'fecha_pago.required_if' => 'La fecha de pago es obligatoria para envíos SJ.',
            'tipos_liq.in' => 'Tipo de liquidación inválido. Valores permitidos: Normal, SAC, Liq. Final o Todas.',
        ]);

        try {
            $identificadorEnvio = $request->identificador_envio;
            $empresa = Sue086::find($request->id_empresa);
            // Filtro de tipoliq elegido por el usuario (null = Todas, TXT global del mes).
            $tiposLiq = $this->normalizarTiposLiq($request->input('tipos_liq', 'todas'));
            // sue100s tiene una fila por (periodo, tipoliq): se prefiere la del tipo pedido para
            // que periodo_id de la emisión apunte al Sue100 correcto; si no existe, la primera.
            $periodo = Sue100::where('periodo', $request->periodo_id)
                ->when($tiposLiq, fn ($q) => $q->whereIn('tipoliq', $tiposLiq))
                ->first()
                ?? Sue100::where('periodo', $request->periodo_id)->first();
            $tipoLiquidacion = $request->tipo_liquidacion;

            if (!$empresa || !$periodo) {
                return response()->json(['success' => false, 'message' => 'Empresa o período no encontrados'], 404);
            }

            $cuit = str_replace('-', '', $empresa->cuit ?? '');

            $periodoStr = $periodo->periodo;

            // Pre-check: conceptos huérfanos en sue090s que no existen en sue102s.
            // En modo 'RE' no se emiten Reg 02/03 (no aplica el chequeo).
            if ($identificadorEnvio === 'SJ') {
                $codEmpresa = $empresa->codigo ?? $empresa->id ?? null;
                $huerfanosQuery = DB::table('sue090s')
                    ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
                    ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                    ->where('sue090s.periodo', $periodoStr)
                    ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
                    ->where(fn ($q) => $q->whereNull('sue001s.baja')->orWhere('sue001s.baja', '>=', $this->inicioPeriodo($periodoStr)))
                    ->whereNull('sue102s.codigo');

                if ($codEmpresa !== null && $codEmpresa !== '') {
                    $huerfanosQuery->where('sue001s.grupo_emp', $codEmpresa);
                }

                $huerfanos = $huerfanosQuery
                    ->select(
                        'sue090s.concepto',
                        DB::raw('MAX(sue090s.descripcion) as descripcion'),
                        DB::raw('COUNT(*) as veces'),
                        DB::raw('SUM(sue090s.importe) as total'),
                        DB::raw('COUNT(DISTINCT sue090s.legajo) as legajos')
                    )
                    ->groupBy('sue090s.concepto')
                    ->orderBy('sue090s.concepto')
                    ->get();

                if ($huerfanos->isNotEmpty()) {
                    return response()->json([
                        'success' => false,
                        'tipo_error' => 'conceptos_huerfanos',
                        'message' => sprintf(
                            'Se encontraron %d conceptos sin parametrizar en el catálogo (Liquidación > Conceptos). ' .
                            'ARCA puede rechazar el archivo. Dá de alta cada uno con su código ARCA antes de generar el LSD.',
                            $huerfanos->count()
                        ),
                        'huerfanos' => $huerfanos->map(fn($h) => [
                            'concepto' => $h->concepto,
                            'descripcion' => $h->descripcion ?? '',
                            'veces' => (int) $h->veces,
                            'legajos' => (int) $h->legajos,
                            'total' => (float) $h->total,
                        ])->values(),
                    ], 422);
                }

                // Pre-check 2: conceptos usados que existen en sue102s pero sin concepto_arca.
                // Se intenta autocompletar desde conceptosarcas (codigo_contribuyente -> codigo_afip);
                // los que no tienen equivalencia se devuelven para que el usuario los complete.
                $sinArcaQuery = DB::table('sue090s')
                    ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
                    ->join('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                    ->where('sue090s.periodo', $periodoStr)
                    ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
                    ->where(fn ($q) => $q->whereNull('sue001s.baja')->orWhere('sue001s.baja', '>=', $this->inicioPeriodo($periodoStr)))
                    ->where(function ($q) {
                        $q->whereNull('sue102s.concepto_arca')
                          ->orWhere('sue102s.concepto_arca', '=', '');
                    });

                if ($codEmpresa !== null && $codEmpresa !== '') {
                    $sinArcaQuery->where('sue001s.grupo_emp', $codEmpresa);
                }

                $sinArca = $sinArcaQuery
                    ->select(
                        'sue090s.concepto',
                        DB::raw('MAX(sue102s.id) as concepto_id'),
                        DB::raw('MAX(sue102s.detalle) as descripcion'),
                        DB::raw('COUNT(*) as veces'),
                        DB::raw('SUM(sue090s.importe) as total'),
                        DB::raw('COUNT(DISTINCT sue090s.legajo) as legajos')
                    )
                    ->groupBy('sue090s.concepto')
                    ->orderBy('sue090s.concepto')
                    ->get();

                $sinArcaPendientes = [];
                foreach ($sinArca as $c) {
                    $codigoAfip = DB::table('conceptosarcas')
                        ->where('codigo_contribuyente', $c->concepto)
                        ->value('codigo_afip');

                    if ($codigoAfip !== null && $codigoAfip !== '') {
                        // Autocompletar concepto_arca en el catálogo (sue102s)
                        DB::table('sue102s')
                            ->where('codigo', $c->concepto)
                            ->update(['concepto_arca' => mb_substr((string) $codigoAfip, 0, 6)]);
                    } else {
                        $sinArcaPendientes[] = $c;
                    }
                }

                if (!empty($sinArcaPendientes)) {
                    return response()->json([
                        'success' => false,
                        'tipo_error' => 'conceptos_sin_arca',
                        'message' => sprintf(
                            'Se encontraron %d conceptos sin código ARCA (concepto_arca) y sin equivalencia en la tabla ARCA. ' .
                            'Asigná el código ARCA a cada uno antes de generar el LSD.',
                            count($sinArcaPendientes)
                        ),
                        'sin_arca' => collect($sinArcaPendientes)->map(fn($c) => [
                            'id' => (int) $c->concepto_id,
                            'concepto' => $c->concepto,
                            'descripcion' => $c->descripcion ?? '',
                            'veces' => (int) $c->veces,
                            'legajos' => (int) $c->legajos,
                            'total' => (float) $c->total,
                        ])->values(),
                    ], 422);
                }
            }

            // Pre-check 3: inconsistencias de datos SICOSS que romperían el formato de ancho fijo del Reg 04.
            // Aplica tanto a SJ como a RE (el Reg 04 se emite en ambos modos).
            // Si el usuario eligió "Ignorar y continuar" (ignorar_inconsistencias=true), no se bloquea:
            // esos legajos se excluyen de la generación y el resto se emite normalmente.
            $inconsistencias = $this->detectarInconsistenciasReg04($empresa, $periodoStr, $tiposLiq);
            $legajosExcluidos = [];
            $legajosIgnorados = [];
            if (!empty($inconsistencias)) {
                if (!$request->boolean('ignorar_inconsistencias')) {
                    return response()->json([
                        'success' => false,
                        'tipo_error' => 'datos_inconsistentes',
                        'message' => sprintf(
                            'Se encontraron %d inconsistencias en los datos SICOSS de los legajos. ' .
                            'Estos valores no entran en el formato de ancho fijo del LSD (Registro 04) y ARCA rechazaría el archivo. ' .
                            'Corregí cada legajo antes de generar.',
                            count($inconsistencias)
                        ),
                        'inconsistencias' => $inconsistencias,
                    ], 422);
                }

                // Códigos de legajo a excluir de la generación (el usuario optó por ignorarlos).
                $legajosExcluidos = array_values(array_filter(array_unique(array_map(
                    fn($i) => (string) ($i['legajo'] ?? ''),
                    $inconsistencias
                )), fn($v) => $v !== ''));

                // Detalle de los legajos ignorados (agrupado por legajo con sus motivos) para persistir
                // en la emisión y mostrarlo luego en el detalle.
                $legajosIgnorados = collect($inconsistencias)
                    ->groupBy('legajo')
                    ->map(function ($grupo) {
                        $primero = $grupo->first();
                        return [
                            'legajo' => (string) ($primero['legajo'] ?? ''),
                            'cuil' => (string) ($primero['cuil'] ?? ''),
                            'nombre' => (string) ($primero['nombre'] ?? ''),
                            'motivos' => collect($grupo)->map(fn($i) => [
                                'campo' => (string) ($i['campo'] ?? ''),
                                'detalle' => (string) ($i['problema'] ?? ''),
                            ])->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();
            }

            // Número de emisión: correlativo ascendente por empresa + período.
            // Solo contamos las emisiones que efectivamente llegaron a ARCA (enviado/confirmado/rechazado).
            // Las que están en borrador/generado son tentativas locales — ARCA no las conoce, no cuentan,
            // por eso el número NO avanza mientras la presentación siga en borrador.
            // El correlativo es por empresa+período sin distinguir tipoliq_filtro: para ARCA la mensual
            // y el SAC del mismo mes son liquidaciones distintas con números consecutivos dentro del período.
            $ultimaEmision = LsdEmision::where('id_empresa', $request->id_empresa)
                ->where('periodo', $periodoStr)
                ->whereIn('estado', ['enviado', 'confirmado', 'rechazado'])
                ->max('numero_emision') ?? 0;
            $numeroEmision = $ultimaEmision + 1;

            $fileData = $this->generarTxt($empresa, $periodo, $tipoLiquidacion, $tiposLiq, $numeroEmision, $request->fecha_pago, $identificadorEnvio, $legajosExcluidos);

            // Si generarTxt devolvió un error (por ejemplo 404), retornarlo y no crear la emisión
            if (!is_array($fileData) || ($fileData['status'] ?? 200) !== 200) {
                $message = is_array($fileData) && isset($fileData['message']) ? $fileData['message'] : 'Error generando archivo';
                return response()->json(['success' => false, 'message' => $message], $fileData['status'] ?? 500);
            }

            // Crear nueva emisión
            $emision = LsdEmision::create([
                'id_empresa' => $request->id_empresa,
                'periodo_id' => $periodo->id,
                'cuit_empresa' => $cuit,
                'numero_emision' => $numeroEmision,
                'fecha_emision' => now()->toDateString(),
                'periodo' => $periodoStr,
                'cantidad_empleados' => $fileData['cantidad_empleados'] ?? 0,
                'legajos_ignorados' => $legajosIgnorados,
                'monto_total' => $fileData['monto_total'] ?? 0,
                'estado' => 'borrador',
                'usuario_id' => auth()->id(),
                'fecha_generacion' => now(),
                'observaciones' => $request->observaciones ?? '',
                'archivo_txt' => $fileData['path'] ?? null,
                'hash_txt' => $fileData['hash_txt'] ?? null,
                'cantidad_lineas' => $fileData['cantidad_lineas'] ?? 0,
                'tipo_liquidacion' => $request->tipo_liquidacion,
                'fecha_pago' => $request->fecha_pago,
                'identificador_envio' => $identificadorEnvio,
                'tipoliq_filtro' => $request->input('tipos_liq', 'todas'),
            ]);

            // Registrar items en lsd_items
            if (!empty($fileData['lsd_items'])) {
                $now = now();
                $itemsToInsert = array_map(function ($item) use ($emision, $now) {
                    $item['lsd_emision_id'] = $emision->id;
                    $item['created_at'] = $now;
                    $item['updated_at'] = $now;
                    return $item;
                }, $fileData['lsd_items']);

                // Insertar en lotes de 500
                foreach (array_chunk($itemsToInsert, 500) as $chunk) {
                    LsdItem::insert($chunk);
                }
            }

            // Devolver JSON con éxito y URL de descarga para que el frontend la procese
            $filename = $fileData['filename'];

            // Control NO bloqueante de aportes (Jubilación/PAMI/OS) vs base × alícuota. El .txt ya se generó;
            // estas diferencias suelen venir de un tope desactualizado en el liquidador de origen.
            $advertenciasAportes = $this->detectarDiferenciasAportes($empresa, $periodoStr, $tiposLiq);

            return response()->json([
                'success' => true,
                'message' => 'Emisión generada exitosamente',
                'download_url' => route('lsd.emision.download', $emision->id),
                'emision_id' => $emision->id,
                'advertencias_aportes' => $advertenciasAportes,
                'legajos_excluidos' => $legajosExcluidos,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la emisión: ' . $e->getMessage() . ' en línea ' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Valida que un registro del TXT tenga exactamente la longitud requerida por ARCA.
     * Lanza DomainException con diagnóstico detallado si no coincide.
     */
    private function validarLongitud(string $linea, int $esperada, string $registro, array $contexto = []): void
    {
        $real = strlen($linea);
        if ($real === $esperada) {
            return;
        }

        $delta = $real - $esperada;
        $diff = $delta > 0 ? "sobran {$delta} caracteres" : 'faltan ' . abs($delta) . ' caracteres';

        $ctxStr = '';
        if (!empty($contexto)) {
            $pares = [];
            foreach ($contexto as $k => $v) {
                $pares[] = "{$k}={$v}";
            }
            $ctxStr = ' [' . implode(', ', $pares) . ']';
        }

        throw new \DomainException(
            "Registro {$registro} con longitud incorrecta{$ctxStr}: " .
            "real {$real} chars, esperado {$esperada} ({$diff}). " .
            "Contenido: \"{$linea}\""
        );
    }

    /**
     * Detecta, para los legajos que entrarían en el LSD del período/empresa, los datos SICOSS que
     * no entran en el formato de ancho fijo del Registro 04 (o que no resuelven a un código válido).
     * Devuelve una fila por (legajo, campo) con problema, lista para mostrar en el modal del frontend.
     */
    private function detectarInconsistenciasReg04($empresa, string $periodoStr, ?array $tiposLiq): array
    {
        $codEmpresa = $empresa->codigo ?? $empresa->id ?? null;

        $query = DB::table('sue090s')
            ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
            ->where('sue090s.periodo', $periodoStr)
            ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
            ->where(fn ($q) => $q->whereNull('sue001s.baja')->orWhere('sue001s.baja', '>=', $this->inicioPeriodo($periodoStr)));

        if ($codEmpresa !== null && $codEmpresa !== '') {
            $query->where('sue001s.grupo_emp', $codEmpresa);
        }

        $empleados = $query
            ->select(
                'sue001s.id',
                'sue001s.codigo',
                'sue001s.cuil',
                'sue001s.detalle',
                'sue001s.nombres',
                'sue001s.alta',
                'sue001s.baja',
                'sue001s.sicoss_zona',
                'sue001s.sicoss_situa',
                'sue001s.sicoss_condi',
                'sue001s.sicoss_activ',
                'sue001s.sicoss_modal',
                'sue001s.sicoss_sini',
                'sue001s.sicoss_hijos',
                'sue001s.sicoss_adherentes',
                'sue001s.obra_sijp',
                'sue001s.jornada_id'
            )
            ->distinct()
            ->get();

        // Codigos válidos de la tabla de zonas/localidades (sue001s.sicoss_zona guarda este codigo interno).
        $zonasValidas = DB::table('sicoss_zonas')->pluck('codigo')->flip();

        // Códigos válidos de obra social (sicoss_obras.codigo: strings de 6 dígitos con ceros a la izquierda).
        $obrasValidas = DB::table('sicoss_obras')->pluck('codigo')->flip();

        // Jornadas (sue010s) y modalidades de contratación "a tiempo parcial" (sicoss08s) para validar
        // coherencia: el prorrateo del importe a detraer y la base diferencial de aportes OS del Reg 04 se
        // disparan por la jornada (sue010s.parcial). La modalidad parcial y la jornada parcial deben coincidir.
        $jornadasPorId = DB::table('sue010s')->get()->keyBy('id');
        $modalidadesParciales = DB::table('sicoss08s')
            ->where('detalle', 'like', '%tiempo parcial%')
            ->pluck('codigo')
            ->map(fn($c) => (int) $c)
            ->all();

        // Campos numéricos del Reg 04 con su ancho fijo: si el valor (como string) supera el ancho, desborda.
        $camposAncho = [
            ['campo' => 'Código Situación',    'col' => 'sicoss_situa',      'ancho' => 2],
            ['campo' => 'Código Condición',    'col' => 'sicoss_condi',      'ancho' => 2],
            ['campo' => 'Código Actividad',    'col' => 'sicoss_activ',      'ancho' => 3],
            ['campo' => 'Código Modalidad',    'col' => 'sicoss_modal',      'ancho' => 3],
            ['campo' => 'Código Siniestrado',  'col' => 'sicoss_sini',       'ancho' => 2],
            ['campo' => 'Cantidad de hijos',   'col' => 'sicoss_hijos',      'ancho' => 2],
            ['campo' => 'Cantidad adherentes', 'col' => 'sicoss_adherentes', 'ancho' => 2],
            ['campo' => 'Código Obra Social',  'col' => 'obra_sijp',         'ancho' => 6],
        ];

        $inconsistencias = [];

        foreach ($empleados as $emp) {
            $base = [
                'id'     => (int) $emp->id,
                'legajo' => (string) ($emp->codigo ?? ''),
                'cuil'   => (string) ($emp->cuil ?? ''),
                // Empleado = apellido (detalle) + nombres.
                'nombre' => trim(((string) ($emp->detalle ?? '')) . ' ' . ((string) ($emp->nombres ?? ''))),
                'alta'   => $emp->alta,
                'baja'   => $emp->baja,
            ];

            // Datos SICOSS sin cargar: el legajo entra en la liquidación pero no tiene los códigos SICOSS
            // obligatorios (quedaría un Reg 04 con situación '00', condición '00', etc. → ARCA lo rechaza).
            // Situación/Condición/Actividad/Modalidad/Localidad faltan si null/vacío/0; Siniestrado solo si
            // null/vacío (0 = "no siniestrado" es un valor válido).
            $faltantesSicoss = [];
            $obligatoriosSicoss = [
                ['col' => 'sicoss_situa', 'label' => 'Situación de revista'],
                ['col' => 'sicoss_condi', 'label' => 'Condición de contratación'],
                ['col' => 'sicoss_activ', 'label' => 'Actividad'],
                ['col' => 'sicoss_modal', 'label' => 'Modalidad de contratación'],
                ['col' => 'sicoss_zona',  'label' => 'Localidad'],
            ];
            foreach ($obligatoriosSicoss as $o) {
                $v = $emp->{$o['col']};
                if ($v === null || $v === '' || (int) $v === 0) {
                    $faltantesSicoss[] = $o['label'];
                }
            }
            if ($emp->sicoss_sini === null || $emp->sicoss_sini === '') {
                $faltantesSicoss[] = 'Código de siniestrado';
            }
            if (!empty($faltantesSicoss)) {
                $inconsistencias[] = $base + [
                    'campo'    => 'Datos SICOSS sin cargar',
                    'valor'    => implode(', ', $faltantesSicoss),
                    'esperado' => 'datos SICOSS completos',
                    'problema' => 'El legajo no tiene cargado(s): ' . implode(', ', $faltantesSicoss) . '. Completá la solapa SICOSS del legajo antes de generar el LSD.',
                ];
            }

            // CUIL: debe tener exactamente 11 dígitos (obligatorio en todos los registros).
            $cuilDigitos = preg_replace('/\D/', '', (string) ($emp->cuil ?? ''));
            if (strlen($cuilDigitos) !== 11) {
                $inconsistencias[] = $base + [
                    'campo'    => 'CUIL',
                    'valor'    => (string) ($emp->cuil ?? ''),
                    'esperado' => '11 dígitos',
                    'problema' => "El CUIL '" . ($emp->cuil ?? '') . "' no tiene 11 dígitos. Es obligatorio en el LSD.",
                ];
            }

            // Localidad: sicoss_zona guarda el codigo interno de sicoss_zonas; debe existir para poder
            // resolver su `numero` (código AFIP de 2 chars). Si no existe, no se puede emitir la localidad.
            $zona = $emp->sicoss_zona;
            if ($zona !== null && $zona !== '' && (int) $zona !== 0 && !$zonasValidas->has($zona)) {
                $inconsistencias[] = $base + [
                    'campo'    => 'Código Localidad',
                    'valor'    => (string) $zona,
                    'esperado' => 'localidad existente en SICOSS',
                    'problema' => "La localidad/zona '{$zona}' del legajo no existe en la tabla SICOSS de zonas. Seleccioná una localidad válida.",
                ];
            }

            // Obra social (obra_sijp): obligatoria y debe existir en el catálogo sicoss_obras
            // (códigos de 6 dígitos con ceros a la izquierda; se normaliza antes de buscar).
            $obra = trim((string) ($emp->obra_sijp ?? ''));
            if ($obra === '') {
                $inconsistencias[] = $base + [
                    'campo'    => 'Obra social',
                    'valor'    => '(vacío)',
                    'esperado' => 'obra social cargada',
                    'problema' => 'El legajo no tiene cargada la obra social (obra_sijp). Asigná una obra social válida.',
                ];
            } elseif (strlen($obra) <= 6 && !$obrasValidas->has(str_pad($obra, 6, '0', STR_PAD_LEFT))) {
                $inconsistencias[] = $base + [
                    'campo'    => 'Obra social',
                    'valor'    => $obra,
                    'esperado' => 'código existente en SICOSS',
                    'problema' => "La obra social '{$obra}' del legajo no existe en la tabla SICOSS de obras sociales. Seleccioná una válida.",
                ];
            }

            // Coherencia jornada parcial ↔ modalidad de contratación a tiempo parcial.
            // El prorrateo del importe a detraer y la base diferencial de aportes OS del Reg 04 se disparan
            // por la jornada (sue010s.parcial). Si la modalidad declara tiempo parcial pero la jornada no
            // (o viceversa), ARCA rechazaría o los aportes de OS no cuadrarían.
            $modalPresente  = !($emp->sicoss_modal === null || $emp->sicoss_modal === '' || (int) $emp->sicoss_modal === 0);
            $modalParcial   = $modalPresente && in_array((int) $emp->sicoss_modal, $modalidadesParciales, true);
            $jornadaRow     = $jornadasPorId[$emp->jornada_id] ?? null;
            $jornadaParcial = $jornadaRow && (int) ($jornadaRow->parcial ?? 0) === 1;
            $jornadaNombre  = $jornadaRow ? (string) $jornadaRow->detalle : '(sin jornada asignada)';

            if ($modalParcial && !$jornadaParcial) {
                $inconsistencias[] = $base + [
                    'campo'    => 'Jornada vs. modalidad',
                    'valor'    => "Modalidad tiempo parcial (cód. {$emp->sicoss_modal}) · Jornada: {$jornadaNombre}",
                    'esperado' => 'jornada de tiempo parcial',
                    'problema' => "La modalidad de contratación es a tiempo parcial (cód. {$emp->sicoss_modal}) pero la jornada asignada ('{$jornadaNombre}') no es de tiempo parcial. Asigná una jornada de tiempo parcial al legajo para que el LSD prorratee el importe a detraer y complete la base diferencial de aportes de obra social.",
                ];
            } elseif ($modalPresente && !$modalParcial && $jornadaParcial) {
                $inconsistencias[] = $base + [
                    'campo'    => 'Jornada vs. modalidad',
                    'valor'    => "Jornada parcial ('{$jornadaNombre}') · Modalidad: cód. {$emp->sicoss_modal}",
                    'esperado' => 'modalidad de contratación a tiempo parcial',
                    'problema' => "La jornada asignada ('{$jornadaNombre}') es de tiempo parcial pero la modalidad de contratación (cód. {$emp->sicoss_modal}) no es a tiempo parcial. Hacé coincidir la modalidad con la jornada para que el importe a detraer y los aportes de obra social del LSD se calculen correctamente.",
                ];
            }

            // Resto de campos numéricos de ancho fijo.
            foreach ($camposAncho as $c) {
                $valor = $emp->{$c['col']};
                if ($valor === null || $valor === '') {
                    continue; // null/vacío se rellena con ceros/espacios, no desborda
                }
                $valorStr = (string) $valor;
                if (strlen($valorStr) > $c['ancho']) {
                    $unidad = $c['col'] === 'obra_sijp' ? 'caracteres' : 'dígitos';
                    $inconsistencias[] = $base + [
                        'campo'    => $c['campo'],
                        'valor'    => $valorStr,
                        'esperado' => "máx. {$c['ancho']} {$unidad}",
                        'problema' => "El valor '{$valorStr}' del campo {$c['campo']} supera el ancho de {$c['ancho']} del Registro 04. Revisá el dato SICOSS del legajo.",
                    ];
                }
            }
        }

        // CUIL duplicado DENTRO de la misma empresa. El pluriempleo es válido solo ENTRE empresas
        // distintas del grupo: la generación filtra por grupo_emp, así que el legajo del mismo CUIL en
        // OTRA empresa ya se ignora automáticamente. Pero si un mismo CUIL aparece en >1 legajo de ESTA
        // empresa, ARCA rechazaría el archivo (dos Reg 04 con el mismo CUIL); hay que dejar uno solo.
        $porCuil = collect($empleados)->groupBy('cuil');
        foreach ($porCuil as $cuil => $grupo) {
            $cuilStr = trim((string) $cuil);
            if ($cuilStr === '') {
                continue;
            }
            $legajos = collect($grupo)->pluck('codigo')->unique()->values();
            if ($legajos->count() > 1) {
                $primero = $grupo->first();
                $inconsistencias[] = [
                    'id'       => (int) $primero->id,
                    'legajo'   => $legajos->implode(', '),
                    'cuil'     => $cuilStr,
                    'nombre'   => trim(((string) ($primero->detalle ?? '')) . ' ' . ((string) ($primero->nombres ?? ''))),
                    'alta'     => $primero->alta,
                    'baja'     => $primero->baja,
                    'campo'    => 'CUIL duplicado en la empresa',
                    'valor'    => 'legajos ' . $legajos->implode(', '),
                    'esperado' => 'CUIL único por empresa',
                    'problema' => "El CUIL {$cuilStr} aparece en {$legajos->count()} legajos de la misma empresa ({$legajos->implode(', ')}). El pluriempleo se admite solo entre empresas distintas del grupo: dejá un único legajo por CUIL en esta empresa (dá de baja o reasigná el otro).",
                ];
            }
        }

        // Ordenar por legajo (numérico-aware) para una lectura cómoda en el modal.
        usort($inconsistencias, fn($a, $b) => strnatcmp($a['legajo'], $b['legajo']));

        return $inconsistencias;
    }

    /**
     * Aportes del trabajador a controlar: alícuota oficial y código ARCA del concepto de descuento.
     * Jubilación 11% (810000), PAMI/INSSJyP 3% (810001), Obra Social 3% (810002).
     */
    private const APORTES_CONTROL = [
        ['nombre' => 'Jubilación',     'arca' => '810000', 'alicuota' => 0.11],
        ['nombre' => 'PAMI (INSSJyP)', 'arca' => '810001', 'alicuota' => 0.03],
        ['nombre' => 'Obra Social',    'arca' => '810002', 'alicuota' => 0.03],
    ];

    /**
     * Control NO bloqueante: compara, por empleado y subsistema, el aporte descontado (concepto del Reg 03)
     * contra el esperado = min(bruto, tope vigente) × alícuota. Sirve para detectar liquidaciones de origen
     * calculadas con un tope desactualizado. Devuelve una fila por (legajo, aporte) con diferencia.
     */
    private function detectarDiferenciasAportes($empresa, string $periodoStr, ?array $tiposLiq): array
    {
        $codEmpresa = $empresa->codigo ?? $empresa->id ?? null;
        $rangosSue089 = DB::table('sue089s')->get();
        $tope = (float) (LsdTope::vigenteParaPeriodo($periodoStr)?->tope_aportes ?? 0);
        // Jornadas indexadas por id: para detectar jornada parcial y omitir el control de OS en esos legajos.
        $jornadasPorId = DB::table('sue010s')->get()->keyBy('id');

        $tiporemPorConcepto = function ($concepto) use ($rangosSue089): ?string {
            foreach ($rangosSue089 as $r) {
                if ($concepto >= $r->desde && $concepto <= $r->hasta) {
                    return strtoupper(trim((string) $r->tiporem));
                }
            }
            return null;
        };

        $query = DB::table('sue090s')
            ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
            ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
            ->where('sue090s.periodo', $periodoStr)
            ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
            ->where(fn ($q) => $q->whereNull('sue001s.baja')->orWhere('sue001s.baja', '>=', $this->inicioPeriodo($periodoStr)));
        if ($codEmpresa !== null && $codEmpresa !== '') {
            $query->where('sue001s.grupo_emp', $codEmpresa);
        }
        $rows = $query->get([
            'sue001s.id as legajo_id',
            'sue001s.codigo as legajo',
            'sue001s.cuil as cuil',
            'sue001s.detalle as apellido',
            'sue001s.nombres as nombres',
            'sue001s.jornada_id as jornada_id',
            'sue090s.concepto',
            'sue090s.importe',
            'sue102s.concepto_arca',
        ]);

        // Agrupar por legajo: bruto (Σ H) y aporte descontado por código ARCA.
        $porLegajo = [];
        foreach ($rows as $row) {
            $leg = (string) $row->legajo;
            if (!isset($porLegajo[$leg])) {
                // Empleado = apellido (detalle) + nombres.
                $nombreCompleto = trim(((string) ($row->apellido ?? '')) . ' ' . ((string) ($row->nombres ?? '')));
                $porLegajo[$leg] = ['legajo_id' => $row->legajo_id, 'cuil' => $row->cuil, 'nombre' => $nombreCompleto, 'jornada_id' => $row->jornada_id, 'bruto' => 0.0, 'brutoSac' => 0.0, 'aportes' => []];
            }
            $arca = (string) ($row->concepto_arca ?? '');
            if ($tiporemPorConcepto($row->concepto) === 'H') {
                $imp = (float) ($row->importe ?? 0);
                $porLegajo[$leg]['bruto'] += $imp;
                // El SAC (concepto ARCA 12xxxx) se topea contra medio tope, aparte del mensual.
                if (str_starts_with($arca, '12')) {
                    $porLegajo[$leg]['brutoSac'] += $imp;
                }
            }
            if ($arca !== '') {
                $porLegajo[$leg]['aportes'][$arca] = ($porLegajo[$leg]['aportes'][$arca] ?? 0.0) + abs((float) ($row->importe ?? 0));
            }
        }

        $diferencias = [];
        foreach ($porLegajo as $leg => $data) {
            $bruto = $data['bruto'];
            // Tope por COMPONENTE en meses con SAC: cada tramo contra su propio techo, sin compensación
            // (min(mensual, tope) + min(SAC, tope/2)). En meses sin SAC, brutoSac=0 → equivale a min(bruto, tope).
            $brutoSac = $data['brutoSac'] ?? 0.0;
            $brutoMensual = max(0.0, $bruto - $brutoSac);
            $base = ($tope > 0)
                ? min($brutoMensual, $tope) + min($brutoSac, $tope / 2)
                : $bruto;

            // Jornada parcial (modalidad 01/21): la base de OBRA SOCIAL no es el bruto real (la determina el
            // liquidador según la categoría, equivalencia a jornada completa + piso). No es reconstruible desde
            // el bruto, así que NO se controla la OS de estos legajos (evita falso positivo). SIPA/PAMI sí.
            $jornada = $jornadasPorId[$data['jornada_id']] ?? null;
            $esJornadaParcial = $jornada && (int) ($jornada->parcial ?? 0) === 1;

            foreach (self::APORTES_CONTROL as $ap) {
                // Obra Social (810002) en jornada parcial: no controlable desde el bruto → se omite.
                if ($esJornadaParcial && $ap['arca'] === '810002') {
                    continue;
                }
                $informado = (float) ($data['aportes'][$ap['arca']] ?? 0);
                // Si no hay concepto de ese aporte descontado, no controlamos (el empleado puede no tributarlo).
                if ($informado <= 0) {
                    continue;
                }
                $esperado = round($base * $ap['alicuota'], 2);
                // Tolerancia de 1 centavo: la diferencia de ±$0,01 es ruido de redondeo (round half-up
                // de PHP vs. truncamiento/half-even del liquidador de origen), no un error real. Se redondea
                // la resta para evitar además el ruido de coma flotante (0,0100000001 > 0,01). Solo se
                // reportan diferencias > $0,01 (topes desactualizados, parametrización, etc.).
                $diferencia = round($esperado - $informado, 2);
                if (abs($diferencia) > 0.01) {
                    $diferencias[] = [
                        'legajo'     => $leg,
                        'legajo_id'  => $data['legajo_id'] !== null ? (int) $data['legajo_id'] : null,
                        'cuil'       => (string) $data['cuil'],
                        'nombre'     => (string) $data['nombre'],
                        'aporte'     => $ap['nombre'],
                        'arca'       => $ap['arca'],
                        'alicuota'   => $ap['alicuota'],
                        'bruto'      => round($bruto, 2),
                        'base'       => round($base, 2),
                        'esperado'   => $esperado,
                        'informado'  => round($informado, 2),
                        'diferencia' => $diferencia,
                    ];
                }
            }
        }

        usort($diferencias, fn($a, $b) => strnatcmp($a['legajo'], $b['legajo']) ?: strcmp($a['aporte'], $b['aporte']));

        return $diferencias;
    }

    /**
     * Ajusta en sue090s los importes de los aportes (Jubilación/PAMI/OS) para que cuadren con
     * base × alícuota (base = min(bruto, tope vigente)). Modifica los datos de la liquidación importada.
     * Recalcula del lado del servidor (no confía en valores del cliente).
     */
    public function ajustarAportes(Request $request)
    {
        $request->validate([
            'id_empresa' => 'required|exists:sue086s,id',
            'periodo_id' => 'required|exists:sue100s,periodo',
            'tipos_liq' => 'nullable|in:1,4,5,todas',
        ], [
            'tipos_liq.in' => 'Tipo de liquidación inválido. Valores permitidos: Normal, SAC, Liq. Final o Todas.',
        ]);

        $empresa = Sue086::find($request->id_empresa);
        $periodo = Sue100::where('periodo', $request->periodo_id)->first();
        if (!$empresa || !$periodo) {
            return response()->json(['success' => false, 'message' => 'Empresa o período no encontrados'], 404);
        }
        $periodoStr = $periodo->periodo;
        // Mismo filtro de tipoliq que usó la generación (null = Todas).
        $tiposLiq = $this->normalizarTiposLiq($request->input('tipos_liq', 'todas'));

        $diferencias = $this->detectarDiferenciasAportes($empresa, $periodoStr, $tiposLiq);
        if (empty($diferencias)) {
            return response()->json(['success' => true, 'ajustados' => 0, 'message' => 'No había diferencias para ajustar.']);
        }

        $ajustados = 0;
        DB::transaction(function () use ($diferencias, $periodoStr, $tiposLiq, &$ajustados) {
            foreach ($diferencias as $dif) {
                // Filas del aporte (puede haber más de una con el mismo código ARCA): se ajusta la primera
                // y las demás se ponen en 0, para que la suma del aporte = esperado.
                $filas = DB::table('sue090s')
                    ->join('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
                    ->where('sue090s.legajo', $dif['legajo'])
                    ->where('sue090s.periodo', $periodoStr)
                    ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
                    ->where('sue102s.concepto_arca', $dif['arca'])
                    ->select('sue090s.id', 'sue090s.importe', 'sue090s.concepto', 'sue090s.tipoliq')
                    ->orderBy('sue090s.id')
                    ->get();

                $primeraFila = $filas->first();
                $primera = true;
                foreach ($filas as $fila) {
                    $signo = ((float) $fila->importe < 0) ? -1 : 1;
                    $nuevo = $primera ? ($signo * $dif['esperado']) : 0.0;
                    DB::table('sue090s')->where('id', $fila->id)->update(['importe' => $nuevo]);
                    $primera = false;
                }

                // Asentar la corrección en el histórico (antes/después a nivel del aporte).
                LiquidacionCorreccion::registrar([
                    'periodo'          => $periodoStr,
                    // Con "Todas" se asienta el tipoliq real de la fila ajustada.
                    'tipoliq'          => $primeraFila->tipoliq ?? null,
                    'legajo'           => (string) $dif['legajo'],
                    'cuil'             => $dif['cuil'] ?? null,
                    'concepto'         => $primeraFila->concepto ?? null,
                    'concepto_arca'    => $dif['arca'] ?? null,
                    'sue090_id'        => $primeraFila->id ?? null,
                    'importe_anterior' => $dif['informado'],
                    'importe_nuevo'    => $dif['esperado'],
                    'motivo'           => "Ajuste de aporte {$dif['aporte']}: " . number_format($dif['informado'], 2, ',', '.') . ' → ' . number_format($dif['esperado'], 2, ',', '.') . ' (base ' . number_format($dif['base'], 2, ',', '.') . ' × ' . rtrim(rtrim(number_format($dif['alicuota'] * 100, 2), '0'), ',') . '%, tope SIPA)',
                    'origen'           => 'ajuste_aportes_lsd',
                ]);

                $ajustados++;
            }
        });

        return response()->json([
            'success' => true,
            'ajustados' => $ajustados,
            'message' => "Se ajustaron {$ajustados} aportes. Generá nuevamente el LSD para obtener el archivo corregido.",
        ]);
    }

    public function generarTxt($empresa, $periodo, $tipoLiquidacion, ?array $tiposLiq, $numero_emision, $fechaPagoOverride = null, $identificadorEnvio = 'SJ', array $legajosExcluidos = [])
    {
        $empresaId = $empresa->id;
        $empresaName = $empresa->detalle ?? '';
        $cuit = str_replace('-', '', $empresa->cuit ?? ''); // Obtener el CUIT de la empresa
        $periodoId = $periodo->id;
        $periodoStr = $periodo->periodo; // Asumiendo que el campo 'periodo' tiene el formato 'YYYY/MM'

        // Fecha de pago: prioridad al override del form; fallback a la del período; último fallback fijo para evitar TXT vacío.
        $fechaPagoEfectiva = $fechaPagoOverride ?: $periodo->fecha_pago;
        $fechaPago = $fechaPagoEfectiva
            ? date('Ymd', strtotime($fechaPagoEfectiva))
            : '20260101'; // Formato YYYYMMDD requerido por SICOSS

        // Identificador del envío:
        //   'SJ' → Liquidación de Sueldos y Jornales + datos DJ F931 (caso normal: emite Reg 01,02,03,04,05).
        //   'RE' → Rectifica SOLO la DJ F931 (emite SOLO Reg 01 y Reg 04; el Reg 01 lleva tipo_liq y días_base en blanco).
        $identificadorEnvio = in_array($identificadorEnvio, ['SJ', 'RE'], true) ? $identificadorEnvio : 'SJ';
        $esRectificativa = $identificadorEnvio === 'RE';

        // Tipo empleador LSD según grilla ARCA (Reg 04 pos 20):
        // 0=Adm.Pública · 1=Dec.814/01 Art 2 Inc.B · 2=Serv.Eventuales Inc.B · 4=Dec.814/01 Inc.A · 5=Serv.Eventuales Inc.A · 7=Enseñanza Privada · 8=Dec.1212/03 AFA Clubes
        $tipoEmpleadorLsd = (string) ($empresa->tipo_empleador_lsd ?? '1');

        // Importe a detraer (Ley 27.430) — preferimos el valor cargado en sue100s.importe_detraer;
        // si está en 0/null, fallback al maestro lsd_importes_detraer vigente para el período.
        $importeDetraerRow = LsdImporteDetraer::vigenteParaPeriodo($periodoStr);
        $importeDetraerNumerico = (float) ($periodo->importe_detraer ?? 0);
        if ($importeDetraerNumerico <= 0) {
            $importeDetraerNumerico = (float) ($importeDetraerRow?->importe ?? 0);
        }
        // Importe para MESES CON SAC (×1,5). Se usa por empleado cuando tiene SAC liquidado (concepto ARCA 12xxxx).
        // Si el maestro no lo tiene cargado, se deriva como 1,5 × el mensual.
        $importeDetraerSacNumerico = (float) ($importeDetraerRow?->importe_sac ?? 0);
        if ($importeDetraerSacNumerico <= 0) {
            $importeDetraerSacNumerico = round($importeDetraerNumerico * 1.5, 2);
        }
        $importeDetraerStr = str_pad((string) (int) round($importeDetraerNumerico * 100), 15, '0', STR_PAD_LEFT);

        // Tope máximo de la base imponible para APORTES (BI 1/4/5), vigente para el período.
        // 0/null = sin tope cargado → no se topea (comportamiento previo). Lo carga el usuario en sicoss/topes.
        $topeAportesNumerico = (float) (LsdTope::vigenteParaPeriodo($periodoStr)?->tope_aportes ?? 0);

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

        // Número de liquidación dentro del período: viene del correlativo por empresa+período calculado en generarEmision().
        // Padding a 5 chars con ceros a la izquierda (Reg 01 pos 23-27, numérico).
        $nroLiquidacion = str_pad((string) $numero_emision, 5, '0', STR_PAD_LEFT);

        // Buscar todos los registros de sue090s
        // $total_haberes    = $items->where('tiporem_calc','H')->sum('importe');
        // $total_descuentos = $items->where('tiporem_calc','D')->sum('importe');
        // $total_adicionales= $items->where('tiporem_calc','NR')->sum('importe');

        $codEmpresa = $empresa->codigo ?? $empresa->id ?? null;

        // Buscar registros de sue090s solo para legajos cuyo grupo_emp en sue001s coincide con $codEmpresa
        $query = DB::table('sue090s')
            ->join('sue001s', 'sue090s.legajo', '=', 'sue001s.codigo')
            ->leftJoin('sue102s', 'sue090s.concepto', '=', 'sue102s.codigo')
            ->leftJoin('sue007s', 'sue001s.convenio', '=', 'sue007s.codigo')
            ->where('sue090s.periodo', $periodoStr)
            ->when($tiposLiq, fn ($q) => $q->whereIn('sue090s.tipoliq', $tiposLiq))
            ->where(fn ($q) => $q->whereNull('sue001s.baja')->orWhere('sue001s.baja', '>=', $this->inicioPeriodo($periodoStr)));

        // ->where('sue090s.legajo', 7009)

        if ($codEmpresa !== null && $codEmpresa !== '') {
            $query->where('sue001s.grupo_emp', $codEmpresa);
        }

        // Legajos excluidos manualmente ("Ignorar y continuar" en el modal de inconsistencias SICOSS).
        if (!empty($legajosExcluidos)) {
            $query->whereNotIn('sue001s.codigo', $legajosExcluidos);
        }

        $datos = $query->select(
            'sue090s.*',
            'sue001s.cuil as cuil',
            'sue001s.codigo as legajo_codigo',
            'sue001s.sicoss_conyuge as conyugue',
            'sue001s.sicoss_hijos as hijos',
            'sue001s.sicoss_adherentes as adherentes',
            'sue001s.sicoss_cob_scvo as sicoss_cob_scvo',
            'sue001s.sicoss_reduccion as sicoss_reduccion',
            'sue001s.sicoss_situa as sicoss_situa',
            'sue001s.sicoss_condi as sicoss_condi',
            'sue001s.sicoss_activ as sicoss_activ',
            'sue001s.sicoss_modal as sicoss_modal',
            'sue001s.sicoss_sini as sicoss_sini',
            'sue001s.sicoss_zona as sicoss_zona',
            'sue001s.obra_sijp as obra_sijp',
            'sue001s.formap as formap',
            'sue001s.cbu as cbu',
            'sue001s.alta as alta',
            'sue001s.baja as baja',
            'sue001s.jornada_id as jornada_id',
            'sue007s.porc_tarea_dif as porc_tarea_dif',
            'sue102s.concepto_arca as concepto_arca'
        )->get();

        // Debug: registrar información no intrusiva sobre $datos
        try {
            Log::debug('LSD datos count: ' . $datos->count());
            $sample = array_slice($datos->toArray(), 0, 20);
            Log::debug('LSD datos sample: ' . json_encode($sample, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // no interrumpir la ejecución por fallos en el logging
        }

        if ($datos->isEmpty()) {
            // Contrato de error de generarTxt: array con status/message (ver generarEmision).
            // Un JsonResponse acá terminaba mostrándose como 500 "Error generando archivo".
            return ['status' => 404, 'message' => 'No se encontraron datos para el período y tipo de liquidación seleccionados.'];
        }

        // -------------------------------------------------------------------
        // Pre-cálculo para $diasTope: parseo del período y vacaciones sue028s
        // -------------------------------------------------------------------
        $periodoPartes = explode('/', $periodoStr); // 'YYYY/MM'
        $periodoAnio = (int) ($periodoPartes[0] ?? date('Y'));
        $periodoMes = (int) ($periodoPartes[1] ?? date('m'));
        $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $periodoMes, 1, $periodoAnio));
        $periodoStr6 = str_pad($periodoAnio, 4, '0', STR_PAD_LEFT) . str_pad($periodoMes, 2, '0', STR_PAD_LEFT); // YYYYMM para sue028s

        // Cargar días de vacaciones de sue028s agrupados por legajo para el período
        $vacacionesPorLegajo = DB::table('sue028s')
            ->where('periodo', $periodoStr6)
            ->where('int_vac', '>', 0)
            ->select('legajo', DB::raw('SUM(int_vac) as total_vac'))
            ->groupBy('legajo')
            ->get()
            ->keyBy('legajo');

        $diasDePeriodo = '30'; // Si “identificación del envío” es igual a “RE”, dejar en blanco 
        $cantidadEmpleados = $datos->unique('cuil')->count(); // Debe coincidir con la cantidad de registros tipo '04' informados en el archivo. Coincide con la cantidad de empleados del F931
        $montoTotal = $datos->sum('importe'); // Asumiendo que hay

        // Si es una rectificativa, se ajustan varios campos y no se informa el tipo de liquidación
        if ($identificadorEnvio == 'RE') {
            $tipoLiquidacion2 = ' '; // En caso de rectificativa, el tipo de liquidación no se informa
            $diasDePeriodo = '  ';
        }

        //---------------------------------------
        // Generar Registro de Encabezado (Tipo 01)
        //---------------------------------------
        $line01 = '01' . $cuit . $identificadorEnvio . $periodoStr . $tipoLiquidacion2 . $nroLiquidacion .
            $diasDePeriodo . str_pad($cantidadEmpleados, 6, '0', STR_PAD_LEFT);

        $this->validarLongitud($line01, 35, '01', [
            'CUIT' => $cuit,
            'periodo' => $periodoStr,
            'empleados' => $cantidadEmpleados,
        ]);

        $contenido = $line01 . "\r\n";

        // Mapeo de sue001s.formap → forma de pago ARCA (Reg 02 pos 115):
        //   'E' efectivo          → '1' efectivo (ARCA)
        //   'D' depósito bancario → '3' acreditación (ARCA)
        //   null/otro             → '1' default efectivo
        // (ARCA también admite '2' cheque, pero no se usa en este sistema)
        // Definido fuera del bloque SJ porque también lo usan los lsd_items, que se arman siempre
        // (en modo RE el bloque Reg 02/03 se saltea y el closure quedaba sin definir → error 500).
        $mapearFormaPago = function ($formap): string {
            return match (strtoupper(trim((string) $formap))) {
                'D' => '3',
                default => '1',
            };
        };

        // En modo 'RE' (rectificativa) ARCA exige que el TXT NO lleve Reg 02 ni Reg 03 ni Reg 05.
        // Solo Reg 01 y Reg 04. Por eso saltamos esos bloques.
        if (!$esRectificativa) {

        //---------------------------------------
        // Generar Registro del Cuerpo (Tipo 02)
        //---------------------------------------
        // $diasTope se calcula por legajo dentro del loop (ver abajo)
        // Cant. de días para proporcionar el tope: se usa cuando el período no es completo
        // (inicio/fin de relación laboral o vacaciones). 0 = período completo sin proporción.

        //$fechaPago = $fechaPago ?? '20260101'; // Fecha de pago en formato YYYYMMDD
        $fechaRubrica = '        ';  // No se completa por el momento

        // Datos (ajusta los campos según tu tabla sue090s)
        // Agrupar por cuil y tomar solo un registro por cuil para este bloque
        $datosPorCuil = $datos->unique('cuil');

        foreach ($datosPorCuil as $registro) {
            // -----------------------------------------------------------
            // Calcular $diasTope por legajo
            // Control 1: alta o baja dentro del período → días proporcionales
            // Control 2: vacaciones en sue028s → restar días de vacaciones
            // -----------------------------------------------------------
            $legajoId = $registro->legajo_codigo ?? $registro->legajo ?? null;
            $workDays = $ultimoDiaMes; // Comenzar con todos los días del mes

            // Control 1a: Alta dentro del período
            if (!empty($registro->alta)) {
                $altaTs = strtotime($registro->alta);
                $altaAnio = (int) date('Y', $altaTs);
                $altaMes = (int) date('n', $altaTs);
                if ($altaAnio === $periodoAnio && $altaMes === $periodoMes) {
                    $diaAlta = (int) date('j', $altaTs);
                    $workDays = $ultimoDiaMes - $diaAlta + 1;
                }
            }

            // Control 1b: Baja dentro del período
            if (!empty($registro->baja)) {
                $bajaTs = strtotime($registro->baja);
                $bajaAnio = (int) date('Y', $bajaTs);
                $bajaMes = (int) date('n', $bajaTs);
                if ($bajaAnio === $periodoAnio && $bajaMes === $periodoMes) {
                    $diaBaja = (int) date('j', $bajaTs);
                    $diasBaja = ($workDays < $ultimoDiaMes) // ya había prorate por alta
                        ? min($workDays, $diaBaja - (int) date('j', strtotime($registro->alta)) + 1)
                        : $diaBaja;
                    $workDays = $diasBaja;
                }
            }

            // Control 2: Vacaciones en sue028s para el período
            if (isset($vacacionesPorLegajo[$legajoId])) {
                $diasVac = (int) $vacacionesPorLegajo[$legajoId]->total_vac;
                $workDays = max(0, $workDays - $diasVac);
            }

            // Si $workDays == $ultimoDiaMes no hubo proporción → informar 0
            $diasTope = ($workDays < $ultimoDiaMes)
                ? str_pad((string) $workDays, 3, '0', STR_PAD_LEFT)
                : '000';

            $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_RIGHT);
            $cuilValue = $registro->cuil ?? '';
            $dependencia = str_pad($registro->dependenciaRevista ?? '', 50, ' ', STR_PAD_LEFT);
            $formaPago = $mapearFormaPago($registro->formap ?? null);
            // CBU: 22 posiciones numéricas. Se exige solo cuando la forma de pago
            // ARCA es '3' (acreditación en cuenta); para efectivo ('1') va en blanco.
            $cbuDigitos = preg_replace('/\D/', '', (string) ($registro->cbu ?? ''));
            $cbu = ($formaPago === '3' && $cbuDigitos !== '')
                ? str_pad(substr($cbuDigitos, 0, 22), 22, '0', STR_PAD_LEFT)
                : str_repeat(' ', 22);

            $line02 = '02'
                . $cuilValue
                . $legajoValue
                . $dependencia
                . $cbu
                . $diasTope
                . $fechaPago
                . $fechaRubrica
                . $formaPago;

            $this->validarLongitud($line02, 115, '02', [
                'CUIL' => $cuilValue,
                'legajo' => trim($legajoValue),
            ]);

            $contenido .= $line02 . "\r\n";
        }

        //---------------------------------------
        // Generar Registro Tipo 03 - Detalle de los conceptos liquidados a cada trabajador
        //---------------------------------------
        // Cargar rangos sue089s antes del Reg 03 para que tanto D/C del Reg 03
        // como la remuneración bruta del Reg 04 usen la MISMA fuente de verdad.
        $rangosSue089 = DB::table('sue089s')->get();

        // Helper: dado un código de concepto, devuelve el tiporem según sue089s ('H', 'NR', 'D' o null).
        $tiporemPorCodigo = function ($codigo) use ($rangosSue089): ?string {
            foreach ($rangosSue089 as $rango) {
                if ($codigo >= $rango->desde && $codigo <= $rango->hasta) {
                    return strtoupper(trim($rango->tiporem ?? ''));
                }
            }
            return null;
        };

        foreach ($datos as $registro) {
            $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_RIGHT);
            $cuilValue = $registro->cuil ?? '';
            $concepto = str_pad($registro->concepto ?? '', 10, ' ', STR_PAD_RIGHT);
            //$cantidad = str_pad(number_format($registro->cantidad ?? 0, 2, '.', ''), 6, ' ', STR_PAD_LEFT);
            $cantidadRaw = $registro->cantidad;
            if ($cantidadRaw === null || $cantidadRaw === '') {
                $cantidad = '00000';
            } else {
                $cantidadInt = (int) round($cantidadRaw * 100);
                $cantidad = $cantidadInt > 99999 ? '99999' : str_pad((string) $cantidadInt, 5, '0', STR_PAD_LEFT);
            }
            $unidades = substr(str_pad($registro->unidades ?? ' ', 1, ' ', STR_PAD_LEFT), 0, 1);
            $importe = str_pad((string) (int) round(abs($registro->importe ?? 0) * 100), 15, '0', STR_PAD_LEFT);

            // D/C según sue089s:
            //   H o NR con importe >= 0 → C (Crédito, suma al bruto)
            //   H o NR con importe < 0  → D (Débito, resta al bruto)
            //   D (descuento)            → D
            //   sin rango               → D (default seguro)
            $rangoConcepto = $tiporemPorCodigo($registro->concepto);
            if (in_array($rangoConcepto, ['H', 'NR'], true) && ($registro->importe ?? 0) >= 0) {
                $debitoCredito = 'C';
            } else {
                $debitoCredito = 'D';
            }

            $periodoAjuste = '      ';

            $line03 = '03'
                . $cuilValue
                . $concepto
                . $cantidad
                . $unidades
                . $importe
                . $debitoCredito
                . $periodoAjuste;

            $this->validarLongitud($line03, 51, '03', [
                'CUIL' => $cuilValue,
                'legajo' => trim($legajoValue),
                'concepto' => trim($concepto),
                'importe' => $registro->importe ?? 0,
            ]);

            $contenido .= $line03 . "\r\n";
        }

        } // fin if (!$esRectificativa) — cierre del bloque que envuelve Reg 02 y Reg 03

        //---------------------------------------
        // Generar Registro Tipo 04 - Atributos de la relación laboral - DJ - Una fila por cada empleado
        // Se agrupa por CUIL y se calcula remuneracionBruta sumando importes H y NR según rangos de sue089s
        // (los $rangosSue089 ya están cargados antes del Reg 03 — reutilizamos la misma fuente).
        //---------------------------------------

        // Mapa codigo (id interno de sicoss_zonas, lo que guarda sue001s.sicoss_zona) → numero (código AFIP real, 2 chars).
        // El campo "Código Localidad" del Reg 04/05 debe llevar el `numero` (ej. codigo 155 = Salta → '62'),
        // NO el codigo/id interno (1-187), que además desborda el campo cuando supera 2 dígitos.
        $zonaNumeroPorCodigo = DB::table('sicoss_zonas')->pluck('numero', 'codigo');

        // Mapa concepto del empleador → marca "contribuciones LRT" de la parametrización ARCA (por empresa).
        // ARCA suma a la BI 9 (LRT) los conceptos NO remunerativos que tienen esta marca (ej. adicionales de
        // acuerdo empresa/sindicato), pero NO los viáticos ni indemnizatorios. Es la fuente de verdad para
        // distinguir NR-con-LRT de NR-sin-LRT cuando comparten el mismo código ARCA (ej. 550000).
        $lrtPorConcepto = DB::table('conceptosarcas')
            ->where('id_empresa', $empresa->id)
            ->pluck('contribuciones_lrt', 'codigo_contribuyente');
        $esConceptoLrt = function ($concepto) use ($lrtPorConcepto): bool {
            $flag = $lrtPorConcepto[$concepto] ?? null;
            return $flag !== null && (float) $flag > 0;
        };

        // Jornadas (sue010s): para detectar media jornada (parcial) y sus horas semanales.
        // En jornada parcial: (1) el importe a detraer se prorratea por horas_semana/48, y
        // (2) se completa la "base diferencial de aportes OS" (la OS se descuenta sobre un mínimo > base real).
        $jornadasPorId = DB::table('sue010s')->get()->keyBy('id');
        $HORAS_JORNADA_COMPLETA = 48; // jornada completa de referencia

        foreach ($datos->unique('cuil') as $registro) {
            // remuneracionBruta: suma de importes H y NR de todos los conceptos del legajo según rangos de sue089s
            $remuneracionBrutaCalculada = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        if (in_array(strtoupper(trim($rango->tiporem)), ['H', 'NR'])) {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);

            // totalHaberes: suma solo de importes remunerativos (tiporem = H)
            $totalHaberesCalculado = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        if (strtoupper(trim($rango->tiporem)) === 'H') {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);

            // Jornada parcial (media jornada): sue001s.jornada_id → sue010s.parcial.
            // factorParcial = horas_semana / 48 (jornada completa). En jornada completa = 1 (sin efecto).
            $jornada = $jornadasPorId[$registro->jornada_id] ?? null;
            $esJornadaParcial = $jornada && (int) ($jornada->parcial ?? 0) === 1;
            $horasSemanaJornada = (float) ($jornada->horas_semana ?? 0);
            $factorParcial = ($esJornadaParcial && $horasSemanaJornada > 0)
                ? min(1.0, $horasSemanaJornada / $HORAS_JORNADA_COMPLETA)
                : 1.0;
            // OS efectivamente descontada (concepto ARCA 810002) del legajo: sirve para derivar la base
            // mínima sobre la que se aportó OS en parcial (aporte / 3%) y completar la base diferencial.
            $osDeducidaCalc = $esJornadaParcial
                ? $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) {
                    return $carry + (((string) ($row->concepto_arca ?? '') === '810002') ? abs((float) ($row->importe ?? 0)) : 0.0);
                }, 0.0)
                : 0.0;

            // BI 9 (LRT): así la determina ARCA a partir de las liquidaciones (Reg 03) + la parametrización:
            //   - TODOS los conceptos remunerativos (H), y
            //   - los NO remunerativos (NR) que tengan la marca "contribuciones LRT" en conceptosarcas.
            // Los NR sin esa marca (viáticos, indemnizatorios, etc.) NO suman, aunque compartan código ARCA.
            $baseImponible9Calculada = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089, $esConceptoLrt) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        $tiporem = strtoupper(trim($rango->tiporem));
                        if ($tiporem === 'H' || ($tiporem === 'NR' && $esConceptoLrt($row->concepto))) {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);
            $baseImponible9Calculada = max(0.0, $baseImponible9Calculada);

            $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_RIGHT);
            $cuilValue = $registro->cuil ?? '';
            $conyugue = str_pad((string) ($registro->conyugue ?? 0), 1, '0', STR_PAD_LEFT);
            $hijos = str_pad((string) ($registro->hijos ?? 0), 2, '0', STR_PAD_LEFT);
            $cct = str_pad((string) ($registro->cct ?? 0), 1, '0', STR_PAD_LEFT);
            $scvo = str_pad((string) ($registro->sicoss_cob_scvo ?? 0), 1, '0', STR_PAD_LEFT);
            $reduccion = str_pad((string) ($registro->sicoss_reduccion ?? 0), 1, '0', STR_PAD_LEFT);
            $tipoempresa = $tipoEmpleadorLsd;
            $tipoOperacion = "0";
            $situacion = str_pad((string) ($registro->sicoss_situa ?? 0), 2, '0', STR_PAD_LEFT);
            $condicion = str_pad((string) ($registro->sicoss_condi ?? 0), 2, '0', STR_PAD_LEFT);
            $actividad = str_pad((string) ($registro->sicoss_activ ?? 0), 3, '0', STR_PAD_LEFT);
            $modalidadContrato = str_pad((string) ($registro->sicoss_modal ?? 0), 3, '0', STR_PAD_LEFT);
            $siniestro = str_pad((string) ($registro->sicoss_sini ?? 0), 2, '0', STR_PAD_LEFT);
            // Localidad: tomar el `numero` (código AFIP, 2 chars) que corresponde al codigo guardado en sicoss_zona.
            $localidad = str_pad((string) ($zonaNumeroPorCodigo[$registro->sicoss_zona] ?? '0'), 2, '0', STR_PAD_LEFT);

            // Situaciones de revista (cambios dentro del mes): hoy se informa una sola situación todo el mes desde el día 1.
            // TODO: implementar cambios intermes leyendo licencias/vacaciones de sue028s o similar.
            $situacionRevista1 = $situacion;
            $diaSituacionRevista1 = "01";
            $situacionRevista2 = "00";
            $diaSituacionRevista2 = "00";
            $situacionRevista3 = "00";
            $diaSituacionRevista3 = "00";

            $cantidadDias = str_pad($registro->cantidadDias ?? '30', 2, '0', STR_PAD_LEFT); // Cantidad de días trabajados en el período. Valor optativo, puede informarse en blanco.
            $cantidadHoras = str_pad($registro->cantidadHoras ?? '0', 3, '0', STR_PAD_LEFT); // Si se informa un valor, el campo Cantidad días trabajados debe ser 0. Formato: 3 dígitos enteros.
            $porcAporteAdicionalSS = str_pad($registro->porcAporteAdicionalSS ?? '0', 5, '0', STR_PAD_LEFT); // Se consignarán los puntos porcentuales que superen los establecidos en la Ley N° 24241, artículo 11 o Decreto N° 1387/01, artículo 15. El programa adicionará el porcentaje adicional que se consigne en el campo al aporte obligatorio vigente a cada periodo y procederá al cálculo sobre la Base Imponible de aportes SIPA. 
            // % Contribución tarea diferencial: ARCA exige 2%-10% cuando el empleado está en un régimen de
            // Servicios Diferenciados (condición 05 = "Servicios Diferenciados", 13 = "Serv. dif. no alcanzados Dto 633/18").
            // Prioridad: el porcentaje configurado en el convenio del empleado (Sue007.porc_tarea_dif).
            // Si el convenio no lo define (0/null), fallback: 2,00% para régimen diferencial, 0 en el resto.
            // Formato: 5 dígitos con 2 decimales implícitos (ej. 2,00% → '00200', 10% → '01000').
            $condicionDiferencial = in_array((int) ($registro->sicoss_condi ?? 0), [5, 13], true);
            $porcTareaDifConvenio = (float) ($registro->porc_tarea_dif ?? 0);
            $porcTareaDif = $porcTareaDifConvenio > 0
                ? $porcTareaDifConvenio
                : ($condicionDiferencial ? 2.00 : 0.0);
            $contribucionTareDif = str_pad((string) (int) round($porcTareaDif * 100), 5, '0', STR_PAD_LEFT);
            // Código de obra social: 6 dígitos con CEROS a la izquierda si hay código (ej. "2501" → "002501");
            // blanco (optativo) solo si el legajo no tiene obra social. Rellenar con espacios rompía códigos
            // guardados sin padear (ARCA rechaza "  2501" por inválido).
            $osRaw = trim((string) ($registro->obra_sijp ?? ''));
            $codObraSocial = $osRaw !== '' ? str_pad($osRaw, 6, '0', STR_PAD_LEFT) : str_repeat(' ', 6);

            $adherentes = str_pad($registro->sicoss_adherentes ?? '00', 2, '0', STR_PAD_LEFT);  // Se registra el número de aquellos que no integran el grupo familiar. Ese dato es tenido en cuenta para el incremento del porcentaje a considerar para el cálculo de aportes de Obra Social.
            $aporteAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán los aportes del trabajador, emergentes de la diferencia entre la remuneración efectivamente percibida por este y el mínimo fijado por ANSES, a los efectos de acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales
            $contribAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán las contribuciones del empleador, emergentes de la diferencia entre la remuneración efectivamente percibida por el trabajador y el mínimo fijado por ANSES, a los efectos de permitirle a este acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales.
            // Jornada parcial: la OS se aporta sobre la base implícita en lo descontado (OS / 3%), topeada.
            // ARCA exige que el BI4 informado (campo pos 221-235) == "determinada" = haberes remunerativos +
            // Base diferencial OS (pos 101-115). Por eso hay que cargar AMBOS: el BI4 con la base completa de OS
            // Y el diferencial = base − haberes (así los dos lados dan la misma base y cuadra).
            $baseOSAportes = ($esJornadaParcial && $osDeducidaCalc > 0)
                ? (($topeAportesNumerico > 0) ? min($osDeducidaCalc / 0.03, $topeAportesNumerico) : $osDeducidaCalc / 0.03)
                : null; // full-time: el BI4 se resuelve más abajo con $baseAportes
            // Base diferencial de aportes OS+FSR (pos 101-115) = base OS − haberes remunerativos (en parcial).
            $baseDifAportesOSNum = ($baseOSAportes !== null)
                ? max(0.0, $baseOSAportes - max(0.0, $totalHaberesCalculado))
                : (float) ($registro->baseCalculoDiferencialAportes ?? 0);
            $baseCalculoDiferencialAportes = str_pad((string) (int) round($baseDifAportesOSNum * 100), 15, '0', STR_PAD_LEFT); // BI 4: diferencial aportes OS+FSR (jornada parcial, Ley 26.474).
            // Base diferencial CONTRIBUCIONES OS+FSR (pos 116-130): mismo criterio que aportes (Art. 92 ter LCT:
            // las contribuciones de OS del parcial son las de un trabajador a tiempo completo de la categoría).
            $baseDifContribOSNum = ($baseOSAportes !== null) ? $baseDifAportesOSNum : (float) ($registro->baseCalculoDiferencialOs ?? 0);
            $baseCalculoDiferencialOS = str_pad((string) (int) round($baseDifContribOSNum * 100), 15, '0', STR_PAD_LEFT); // BI 8: diferencial contribuciones OS+FSR (jornada parcial, Ley 26.474 art 1 inc 4).
            $baseCalculoDiferencialLRT = str_pad($registro->baseCalculoDiferencialLRT ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 9 (contribuciones LRT) . Formato: 13 dígitos enteros y 2 decimales.
            // Remuneración Maternidad (pos 146-160): para empleadas en licencia por maternidad (situación de revista
            // 5 = maternidad, 10 = excedencia, 11 = maternidad Down) se informa la remuneración bruta que le hubiera
            // correspondido percibir (la usa ANSeS para la asignación). Se calcula como la suma de los haberes
            // remunerativos positivos (créditos H), ya que el haber de maternidad suele netearse con una "ausencia".
            $remMaternidadCalculada = in_array((int) ($registro->sicoss_situa ?? 0), [5, 10, 11], true)
                ? $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089) {
                    foreach ($rangosSue089 as $rango) {
                        if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                            if (strtoupper(trim($rango->tiporem)) === 'H' && (float) ($row->importe ?? 0) > 0) {
                                $carry += (float) $row->importe;
                            }
                            break;
                        }
                    }
                    return $carry;
                }, 0.0)
                : 0.0;
            $remuneracionMaternidad = str_pad((string) (int) round($remMaternidadCalculada * 100), 15, '0', STR_PAD_LEFT); // Monto de la remuneración bruta que le hubiera correspondido percibir. Formato: 13 enteros + 2 decimales.

            // remuneracionBruta: suma de importes H y NR del legajo según rangos de sue089s (13 enteros + 2 decimales implícitos)
            $remuneracionBruta = str_pad((string) (int) round($remuneracionBrutaCalculada * 100), 15, '0', STR_PAD_LEFT);
            $totalHaberes = str_pad((string) (int) round($totalHaberesCalculado * 100), 15, '0', STR_PAD_LEFT);

            Log::debug('Bruto: ' . $remuneracionBruta);

            // Bases imponibles: ARCA recalcula sumando solo los Reg 03 cuyos conceptos ARCA tributan a cada subsistema.
            // En este sistema, los conceptos NR no tienen mapeo ARCA específico para OS/SIPA, por lo que ARCA los excluye.
            // Por eso BI 2/4/8 usan $totalHaberes (solo H) — la regla previa "BI = Bruto" generaba diferencias contra ARCA.
            //
            // Tope de APORTES: las bases de aportes (BI 1 SIPA, BI 4 OS, BI 5 INSSJyP) se topean al tope máximo
            // vigente. ARCA recalcula el aporte como BI × alícuota; si la BI va sin topear, el aporte calculado por
            // ARCA supera al efectivamente descontado y rechaza ("El aporte de SIPA calculado ... difiere"). Las
            // CONTRIBUCIONES (BI 2/3/8) no tienen tope.
            $baseAportes = ($topeAportesNumerico > 0)
                ? min($totalHaberesCalculado, $topeAportesNumerico)
                : $totalHaberesCalculado;
            $baseAportesStr = str_pad((string) (int) round($baseAportes * 100), 15, '0', STR_PAD_LEFT);
            $baseImponible1 = $baseAportesStr; // Aportes SIPA (topeada).
            $baseImponible2 = $totalHaberes; // Contribuciones SIPA e INSSJyP (sin tope).
            $baseImponible3 = $totalHaberes; // Contribuciones FNE / asignaciones familiares / RENATRE (sin tope).
            // Aportes Obra Social y FSR (topeada). En jornada parcial el BI4 lleva la base completa de OS
            // ($baseOSAportes, calculada arriba); full-time usa la base de aportes normal. SIPA (BI1) y PAMI (BI5)
            // siguen sobre los haberes reales.
            $baseImponible4 = str_pad((string) (int) round(($baseOSAportes ?? $baseAportes) * 100), 15, '0', STR_PAD_LEFT);
            $baseImponible5 = $baseAportesStr; // Aportes INSSJyP (topeada).
            $baseImponible6 = str_pad($registro->baseImponible6 ?? '0', 15, '0', STR_PAD_LEFT); // Aportes diferenciales.
            $baseImponible7 = str_pad($registro->baseImponible7 ?? '0', 15, '0', STR_PAD_LEFT); // Aportes regímenes especiales.
            // Contribuciones Obra Social y FSR. En parcial = base completa de OS (igual que BI4); el incremento
            // sobre los haberes va en la base diferencial de contribuciones OS (pos 116-130). Art. 92 ter LCT.
            $baseImponible8 = ($baseOSAportes !== null)
                ? str_pad((string) (int) round($baseOSAportes * 100), 15, '0', STR_PAD_LEFT)
                : $totalHaberes;
            // BI 9 (LRT) = H + NR-con-marca-LRT (ver cálculo de $baseImponible9Calculada arriba).
            // Coincide con la base que ARCA determina a partir de las liquidaciones + parametrización.
            $baseImponible9 = str_pad((string) (int) round($baseImponible9Calculada * 100), 15, '0', STR_PAD_LEFT);
            $baseCalculoDiferencialAportesSS = str_pad($registro->baseCalculoDiferencialAportesSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 1 Formato: 13 dígitos enteros y 2 decimales. 
            $baseCalculoDiferencialContribSS = str_pad($registro->baseCalculoDiferencialContribSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 2 Formato: 13 dígitos enteros y 2 decimales. 
            // BI 10 (Ley 27.430) = base de contribuciones (conceptos H) − importe a detraer.
            // La detracción NO puede superar la base (no genera base negativa): se topea al valor de la base.
            // Así, cuando la base es 0 (ej. licencia por maternidad con haberes neteados, o meses sin haberes),
            // el importe a detraer informado es 0 y BI 10 = 0, consistente con lo que ARCA determina.
            $baseBI10 = max(0.0, $totalHaberesCalculado);
            // Mes con SAC: si el empleado tiene concepto de SAC liquidado (concepto ARCA 12xxxx),
            // la detracción es la ×1,5 (importe_sac); si no, la mensual. Se evalúa sobre las filas
            // que entraron al TXT según el filtro de tipoliq: con "SAC" solo, también aplica la ×1,5
            // completa (no la mitad) — comportamiento aceptado, pensado para el TXT global (Todas).
            $tieneSac = $datos->where('cuil', $registro->cuil)->contains(
                fn ($row) => str_starts_with((string) ($row->concepto_arca ?? ''), '12')
            );
            $detraccionMensual = $tieneSac ? $importeDetraerSacNumerico : $importeDetraerNumerico;
            // Jornada parcial: la detracción se prorratea por horas (factorParcial = horas_semana/48).
            $detraccionEmpleado = round($detraccionMensual * $factorParcial, 2);
            // Tope estructural de ARCA para tiempo parcial: la detracción no puede superar el 67% del valor
            // (Art. 92 ter: el parcial trabaja < 2/3 de la jornada habitual). Red de seguridad por si un dato
            // de jornada mal cargado diera un factor > 0,67. Ej. mensual 7.003,68 → tope 4.692,47.
            if ($esJornadaParcial) {
                $detraccionEmpleado = min($detraccionEmpleado, round($detraccionMensual * 0.67, 2));
            }
            // Modalidades que no admiten la detracción (Guía N°17 ARCA): no aportan a SIPA → no hay
            // base. ARCA exige importe a detraer = 0 Y BI 10 = 0. Para el resto: BI 10 = base − detracción
            // (detracción topeada a la base, no genera base negativa).
            if ($this->modalidadSinDetraccion($registro->sicoss_modal ?? 0)) {
                $detraerAplicado = 0.0;
                $bi10Calc = 0.0;
            } else {
                $detraerAplicado = min($detraccionEmpleado, $baseBI10);
                $bi10Calc = $baseBI10 - $detraerAplicado;
            }
            $baseImponible10 = str_pad((string) (int) round($bi10Calc * 100), 15, '0', STR_PAD_LEFT);
            $importeDetraer = str_pad((string) (int) round($detraerAplicado * 100), 15, '0', STR_PAD_LEFT);

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
                . $baseImponible10
                . $importeDetraer;

            $this->validarLongitud($line04, 370, '04', [
                'CUIL' => $cuilValue,
                'legajo' => trim($legajoValue),
                'situacion' => trim($situacion),
                'modalidad' => trim($modalidadContrato),
            ]);

            $contenido .= $line04 . "\r\n";
        }


        //---------------------------------------
        // Generar Registro Tipo 05 - Trabajadores Eventuales - Una fila por cada empleado declarado con modalidad 102 en el registro 4
        //---------------------------------------
        if (!$esRectificativa) { foreach ($datos as $registro) {
            $legajoValue = str_pad($registro->legajo_codigo ?? $registro->legajo ?? '', 10, ' ', STR_PAD_RIGHT);
            $cuilValue = $registro->cuil ?? '';
            $conyugue = str_pad((string) ($registro->conyugue ?? 0), 1, '0', STR_PAD_LEFT);
            $hijos = str_pad((string) ($registro->hijos ?? 0), 2, '0', STR_PAD_LEFT);
            $cct = str_pad((string) ($registro->cct ?? 0), 1, '0', STR_PAD_LEFT);
            $scvo = str_pad((string) ($registro->sicoss_cob_scvo ?? 0), 1, '0', STR_PAD_LEFT);
            $reduccion = str_pad((string) ($registro->sicoss_reduccion ?? 0), 1, '0', STR_PAD_LEFT);
            $tipoempresa = $tipoEmpleadorLsd;
            $tipoOperacion = "0";
            $situacion = str_pad((string) ($registro->sicoss_situa ?? 0), 2, '0', STR_PAD_LEFT);
            $condicion = str_pad((string) ($registro->sicoss_condi ?? 0), 2, '0', STR_PAD_LEFT);
            $actividad = str_pad((string) ($registro->sicoss_activ ?? 0), 3, '0', STR_PAD_LEFT);
            $modalidadContrato = str_pad((string) ($registro->sicoss_modal ?? 0), 3, '0', STR_PAD_LEFT);
            $siniestro = str_pad((string) ($registro->sicoss_sini ?? 0), 2, '0', STR_PAD_LEFT);
            // Localidad: numero (código AFIP, 2 chars) mapeado desde el codigo guardado en sicoss_zona.
            $localidad = str_pad((string) ($zonaNumeroPorCodigo[$registro->sicoss_zona] ?? '0'), 2, '0', STR_PAD_LEFT);
            $situacionRevista1 = $situacion;
            $diaSituacionRevista1 = "01";
            $situacionRevista2 = "00";
            $diaSituacionRevista2 = "00";
            $situacionRevista3 = "00";
            $diaSituacionRevista3 = "00";
            $cantidadDias = str_pad($registro->cantidadDias ?? '30', 2, '0', STR_PAD_LEFT); // Cantidad de días trabajados en el período. Valor optativo, puede informarse en blanco.
            $cantidadHoras = str_pad($registro->cantidadHoras ?? '0', 3, '0', STR_PAD_LEFT); // Si se informa un valor, el campo Cantidad días trabajados debe ser 0. Formato: 3 dígitos enteros.
            $porcAporteAdicionalSS = str_pad($registro->porcAporteAdicionalSS ?? '0', 5, '0', STR_PAD_LEFT); // Se consignarán los puntos porcentuales que superen los establecidos en la Ley N° 24241, artículo 11 o Decreto N° 1387/01, artículo 15. El programa adicionará el porcentaje adicional que se consigne en el campo al aporte obligatorio vigente a cada periodo y procederá al cálculo sobre la Base Imponible de aportes SIPA. 
            // % Contribución tarea diferencial: prioriza el convenio (Sue007.porc_tarea_dif); fallback 2,00% para
            // Servicios Diferenciados (condición 05/13), 0 en el resto. Igual que en Reg 04.
            $condicionDiferencial = in_array((int) ($registro->sicoss_condi ?? 0), [5, 13], true);
            $porcTareaDifConvenio = (float) ($registro->porc_tarea_dif ?? 0);
            $porcTareaDif = $porcTareaDifConvenio > 0
                ? $porcTareaDifConvenio
                : ($condicionDiferencial ? 2.00 : 0.0);
            $contribucionTareDif = str_pad((string) (int) round($porcTareaDif * 100), 5, '0', STR_PAD_LEFT);
            // Código de obra social (ídem Reg 04): obra_sijp con ceros a la izquierda, o blanco si no tiene.
            $osRaw05 = trim((string) ($registro->obra_sijp ?? ''));
            $codObraSocial = $osRaw05 !== '' ? str_pad($osRaw05, 6, '0', STR_PAD_LEFT) : str_repeat(' ', 6);
            $adherentes = "00";  // Se registra el número de aquellos que no integran el grupo familiar. Ese dato es tenido en cuenta para el incremento del porcentaje a considerar para el cálculo de aportes de Obra Social.
            $aporteAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán los aportes del trabajador, emergentes de la diferencia entre la remuneración efectivamente percibida por este y el mínimo fijado por ANSES, a los efectos de acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales
            $contribAdicionalOS = str_pad($registro->aporteAdicionalOS ?? '0', 15, '0', STR_PAD_LEFT); // Se consignarán las contribuciones del empleador, emergentes de la diferencia entre la remuneración efectivamente percibida por el trabajador y el mínimo fijado por ANSES, a los efectos de permitirle a este acceder a una cobertura médico asistencial (Dec. 492/95, art. 8) Formato: 13 dígitos enteros y 2 decimales. 
            // $baseDifAportesOS05 / $baseCalculoDiferencialAportes se calculan más abajo, una vez definidas
            // las variables de jornada parcial ($esJornadaParcial05, $osDeducida05) y $totalHaberesCalc05.
            $baseCalculoDiferencialOS = str_pad($registro->baseCalculoDiferencialOs ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 8 (contribuciones de obra social y FSR) en los casos de trabajadores a tiempo parcial que contribuyen como tiempo completo (Ley, 26.474 art 1, inc. 4) Formato: 13 dígitos enteros y 2 decimales. 
            $baseCalculoDiferencialLRT = str_pad($registro->baseCalculoDiferencialLRT ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 9 (contribuciones LRT) . Formato: 13 dígitos enteros y 2 decimales. 
            $remuneracionMaternidad = str_pad($registro->remuneracionMaternidad ?? '0', 15, '0', STR_PAD_LEFT); // Informará el monto de la remuneración bruta que le hubiera correspondido percibir a la trabajadora si hubiera cumplido sus servicios normalmente.  Formato: 13 dígitos enteros y 2 decimales. 
            // remuneracionBruta: suma de importes H y NR del legajo según rangos de sue089s (13 enteros + 2 decimales implícitos)
            $remuneracionBrutaCalc05 = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        if (in_array(strtoupper(trim($rango->tiporem)), ['H', 'NR'])) {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);

            // totalHaberes: suma solo de importes remunerativos (tiporem = H)
            $totalHaberesCalc05 = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        if (strtoupper(trim($rango->tiporem)) === 'H') {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);
            // Jornada parcial (igual que en Reg 04): factor de prorrateo del detraer y OS descontada para la base diferencial.
            $jornada05 = $jornadasPorId[$registro->jornada_id] ?? null;
            $esJornadaParcial05 = $jornada05 && (int) ($jornada05->parcial ?? 0) === 1;
            $horasSemana05 = (float) ($jornada05->horas_semana ?? 0);
            $factorParcial05 = ($esJornadaParcial05 && $horasSemana05 > 0)
                ? min(1.0, $horasSemana05 / $HORAS_JORNADA_COMPLETA)
                : 1.0;
            $osDeducida05 = $esJornadaParcial05
                ? $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) {
                    return $carry + (((string) ($row->concepto_arca ?? '') === '810002') ? abs((float) ($row->importe ?? 0)) : 0.0);
                }, 0.0)
                : 0.0;
            // Jornada parcial (igual criterio que Reg 04): el BI4 lleva la base completa de OS (OS/3%, topeada) Y
            // el diferencial (pos 101-115) = base − haberes, porque ARCA determina BI4 = haberes + diferencial y
            // exige que coincida con el BI4 informado.
            $baseOSAportes05 = ($esJornadaParcial05 && $osDeducida05 > 0)
                ? (($topeAportesNumerico > 0) ? min($osDeducida05 / 0.03, $topeAportesNumerico) : $osDeducida05 / 0.03)
                : null;
            $baseDifAportesOS05 = ($baseOSAportes05 !== null)
                ? max(0.0, $baseOSAportes05 - max(0.0, $totalHaberesCalc05))
                : (float) ($registro->baseCalculoDiferencialAportes ?? 0);
            $baseCalculoDiferencialAportes = str_pad((string) (int) round($baseDifAportesOS05 * 100), 15, '0', STR_PAD_LEFT); // BI 4: diferencial aportes OS+FSR (jornada parcial, Ley 26.474).
            // Diferencial CONTRIBUCIONES OS+FSR (pos 116-130): mismo criterio que aportes (Art. 92 ter LCT). Sobrescribe
            // el default de arriba para parcial.
            if ($baseOSAportes05 !== null) {
                $baseCalculoDiferencialOS = str_pad((string) (int) round($baseDifAportesOS05 * 100), 15, '0', STR_PAD_LEFT);
            }
            $remuneracionBruta = str_pad((string) (int) round($remuneracionBrutaCalc05 * 100), 15, '0', STR_PAD_LEFT);
            // Reg 05 (eventuales): BI 1-8 usan $totalHaberes (solo H); BI 9 (LRT) = H + NR-con-marca-LRT.
            $totalHaberes05Str = str_pad((string) (int) round($totalHaberesCalc05 * 100), 15, '0', STR_PAD_LEFT);
            $baseImponible9Calc05 = $datos->where('cuil', $registro->cuil)->reduce(function (float $carry, $row) use ($rangosSue089, $esConceptoLrt) {
                foreach ($rangosSue089 as $rango) {
                    if ($row->concepto >= $rango->desde && $row->concepto <= $rango->hasta) {
                        $tiporem = strtoupper(trim($rango->tiporem));
                        if ($tiporem === 'H' || ($tiporem === 'NR' && $esConceptoLrt($row->concepto))) {
                            $carry += (float) ($row->importe ?? 0);
                        }
                        break;
                    }
                }
                return $carry;
            }, 0.0);
            // Aportes (BI 1/4/5) topeados al tope máximo vigente; contribuciones (BI 2/3/8) sin tope (ver Reg 04).
            $baseAportes05 = ($topeAportesNumerico > 0)
                ? min($totalHaberesCalc05, $topeAportesNumerico)
                : $totalHaberesCalc05;
            $baseAportes05Str = str_pad((string) (int) round($baseAportes05 * 100), 15, '0', STR_PAD_LEFT);
            $baseImponible1 = $baseAportes05Str;
            $baseImponible2 = $totalHaberes05Str;
            $baseImponible3 = $totalHaberes05Str;
            // BI4 (aportes OS): en parcial = base completa de OS ($baseOSAportes05, calculada arriba); resto $baseAportes05.
            $baseImponible4 = str_pad((string) (int) round(($baseOSAportes05 ?? $baseAportes05) * 100), 15, '0', STR_PAD_LEFT);
            $baseImponible5 = $baseAportes05Str;
            $baseImponible6 = str_pad($registro->baseImponible6 ?? '0', 15, '0', STR_PAD_LEFT);
            $baseImponible7 = str_pad($registro->baseImponible7 ?? '0', 15, '0', STR_PAD_LEFT);
            // Contribuciones OS y FSR. En parcial = base completa de OS (igual que BI4); Art. 92 ter LCT.
            $baseImponible8 = ($baseOSAportes05 !== null)
                ? str_pad((string) (int) round($baseOSAportes05 * 100), 15, '0', STR_PAD_LEFT)
                : $totalHaberes05Str;
            $baseImponible9 = str_pad((string) (int) round(max(0.0, $baseImponible9Calc05) * 100), 15, '0', STR_PAD_LEFT);
            $baseCalculoDiferencialAportesSS = str_pad($registro->baseCalculoDiferencialAportesSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 1 Formato: 13 dígitos enteros y 2 decimales. 
            $baseCalculoDiferencialContribSS = str_pad($registro->baseCalculoDiferencialContribSS ?? '0', 15, '0', STR_PAD_LEFT); // Para informar diferenciales que sumen a la base imponible 2 Formato: 13 dígitos enteros y 2 decimales. 
            // BI 10 (Ley 27.430) = base de conceptos H − importe a detraer, con detracción topeada a la base
            // (no supera la base; ver comentario equivalente en Reg 04).
            $baseBI10_05 = max(0.0, $totalHaberesCalc05);
            // Mes con SAC (ver Reg 04): ×1,5 si el empleado tiene concepto de SAC (ARCA 12xxxx).
            $tieneSac05 = $datos->where('cuil', $registro->cuil)->contains(
                fn ($row) => str_starts_with((string) ($row->concepto_arca ?? ''), '12')
            );
            $detraccionMensual05 = $tieneSac05 ? $importeDetraerSacNumerico : $importeDetraerNumerico;
            $detraccionEmpleado05 = round($detraccionMensual05 * $factorParcial05, 2); // prorrateo por jornada parcial
            // Tope estructural ARCA para parcial: máx. 67% del valor (ver Reg 04).
            if ($esJornadaParcial05) {
                $detraccionEmpleado05 = min($detraccionEmpleado05, round($detraccionMensual05 * 0.67, 2));
            }
            // Modalidades sin detracción (ver Reg 04): importe a detraer = 0 Y BI 10 = 0.
            if ($this->modalidadSinDetraccion($registro->sicoss_modal ?? 0)) {
                $detraerAplicado05 = 0.0;
                $bi10Calc05 = 0.0;
            } else {
                $detraerAplicado05 = min($detraccionEmpleado05, $baseBI10_05);
                $bi10Calc05 = $baseBI10_05 - $detraerAplicado05;
            }
            $baseImponible10 = str_pad((string) (int) round($bi10Calc05 * 100), 15, '0', STR_PAD_LEFT);
            $importeDetraer = str_pad((string) (int) round($detraerAplicado05 * 100), 15, '0', STR_PAD_LEFT);

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

                $this->validarLongitud($line05, 65, '05', [
                    'CUIL' => $cuilValue,
                    'legajo' => trim($legajoValue),
                    'modalidad' => trim($modalidadContrato),
                ]);

                $contenido .= $line05 . "\r\n";
            }
        } } // fin foreach Reg 05 + fin if (!$esRectificativa)

        //$contenido .= str_repeat('-', 80) . "\n";
        //$contenido .= "TOTAL REGISTROS: " . $datos->count() . "\n";

        // Guardar en storage/app/lsd y devolver información para descarga
        $dir = storage_path('app/lsd');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Tipo de liquidación emitida en el nombre del archivo (sin espacios ni puntos).
        $nombresTipoLiq = [1 => 'Normal', 4 => 'SAC', 5 => 'LiqFinal'];
        $tipoLiqArchivo = $tiposLiq === null
            ? 'Todas'
            : ($nombresTipoLiq[$tiposLiq[0]] ?? ('Tipo' . $tiposLiq[0]));

        $filename = "LSD_{$empresaName}_liq_{$numero_emision}_periodo_{$periodoId}_{$tipoLiqArchivo}_" . date('Ymd_His') . ".txt";
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $contentToWrite = rtrim($contenido, "\r\n");

        try {
            file_put_contents($fullPath, $contentToWrite);
        } catch (\Throwable $e) {
            return [
                'status' => 500,
                'message' => 'No se pudo guardar el archivo: ' . $e->getMessage() . ' en línea ' . $e->getLine(),
                'line' => $e->getLine(),
            ];
        }

        $hashTxt = hash('sha256', $contentToWrite);
        $cantidadLineas = $contentToWrite === '' ? 0 : substr_count($contentToWrite, "\n") + 1;

        // Preparar items para lsd_items (uno por cada concepto/registro tipo 03)
        $lsdItems = [];
        foreach ($datos as $registro) {
            $lsdItems[] = [
                'cuil' => $registro->cuil ?? '',
                'legajo' => $registro->legajo_codigo ?? $registro->legajo ?? '',
                'codigo_concepto' => $registro->concepto ?? '',
                'cantidad' => $registro->cantidad ?? 0,
                'unidades' => $registro->unidades ?? '',
                'importe' => $registro->importe ?? 0,
                'debito_credito' => (in_array($registro->tiporem, ['sue', 'nre', 'adi', 'hse', 'sac']) && ($registro->importe ?? 0) >= 0) ? 'C' : 'D',
                'periodo_ajuste' => null,
                'fecha_pago' => $fechaPagoEfectiva,
                'forma_pago' => $mapearFormaPago($registro->formap ?? null),
            ];
        }

        return [
            'status' => 200,
            'path' => $fullPath,
            'filename' => $filename,
            'hash_txt' => $hashTxt,
            'cantidad_lineas' => $cantidadLineas,
            'cantidad_empleados' => $cantidadEmpleados,
            'monto_total' => $montoTotal,
            'lsd_items' => $lsdItems,
        ];
    }


    /**
     * Mostrar detalle completo de una emisión con sus items
     */
    public function detalle($id)
    {
        $emision = LsdEmision::findOrFail($id);
        $items = LsdItem::where('lsd_emision_id', $emision->id)
            ->orderBy('cuil')
            ->orderBy('codigo_concepto')
            ->get();
        $empresa = Sue086::find($emision->id_empresa);

        // Rangos de tipo de concepto (H=Remunerativo, NR=No Remunerativo, D=Descuento) por código.
        $rangos = DB::table('sue089s')->get();
        $tipoDe = function ($concepto) use ($rangos): ?string {
            foreach ($rangos as $r) {
                if ($concepto >= $r->desde && $concepto <= $r->hasta) {
                    return strtoupper(trim((string) $r->tiporem));
                }
            }
            return null;
        };

        // Descripción de conceptos y nombres de empleados (apellido = detalle, nombre = nombres).
        $descConcepto = DB::table('sue102s')->pluck('detalle', 'codigo');
        $codEmpresa = $empresa->codigo ?? null;
        $empleados = DB::table('sue001s')
            ->when($codEmpresa, fn($q) => $q->where('grupo_emp', $codEmpresa))
            ->get(['codigo', 'cuil', 'detalle', 'nombres', 'convenio'])
            ->keyBy('cuil');

        // Convenios (sue007s) y tipo de liquidación (de la emisión).
        $convenios = DB::table('sue007s')->pluck('detalle', 'codigo');
        $tiposLiq = [1 => 'Normal', 2 => '1er. Quincena', 3 => '2da. Quincena', 4 => 'SAC', 5 => 'Liq. Final', 6 => 'DIF.HAB.'];
        if ($emision->tipoliq_filtro !== null && $emision->tipoliq_filtro !== '') {
            // Emisiones nuevas: el filtro elegido al generar ('todas' o un tipoliq del Mapa A).
            $tipoLiqNombre = $emision->tipoliq_filtro === 'todas'
                ? 'Todas'
                : ($tiposLiq[(int) $emision->tipoliq_filtro] ?? ('Tipo ' . $emision->tipoliq_filtro));
        } else {
            // Fallback legacy (emisiones previas al filtro): usa tipo_liquidacion (M/Q/D/H),
            // que no es el Mapa A — se mantiene solo por compatibilidad histórica.
            $tipoLiqNombre = $tiposLiq[(int) $emision->tipo_liquidacion] ?? ('Tipo ' . $emision->tipo_liquidacion);
        }

        // Bases imponibles y cálculos del Reg 04 (parseados del TXT generado, por CUIL).
        $basesPorCuil = $this->parsearReg04($emision->archivo_txt);

        // Resumen por empleado (totalizador) + detalle completo para el modal.
        $resumen = [];
        $detallePorCuil = [];
        foreach ($items->groupBy('cuil') as $cuil => $grupo) {
            $rem = 0.0; $norem = 0.0; $desc = 0.0;
            $conceptos = [];
            foreach ($grupo as $it) {
                $tipo = $tipoDe((int) $it->codigo_concepto);
                $imp = (float) $it->importe;
                if ($tipo === 'H') {
                    $rem += $imp;
                } elseif ($tipo === 'NR') {
                    $norem += $imp;
                } elseif ($tipo === 'D') {
                    $desc += $imp;
                }
                $conceptos[] = [
                    'concepto' => $it->codigo_concepto,
                    'descripcion' => $descConcepto[$it->codigo_concepto] ?? '',
                    'tipo' => $tipo,
                    'cantidad' => $it->cantidad,
                    'importe' => $imp,
                    'debito_credito' => $it->debito_credito,
                ];
            }
            $emp = $empleados[$cuil] ?? null;
            $resumen[] = [
                'tipo_liq' => $tipoLiqNombre,
                'convenio' => $emp ? ($convenios[$emp->convenio] ?? (string) ($emp->convenio ?? '')) : '',
                'cuil' => (string) $cuil,
                'nombre' => $emp ? trim(((string) ($emp->detalle ?? '')) . ' ' . ((string) ($emp->nombres ?? ''))) : '',
                'legajo' => $grupo->first()->legajo,
                'remunerativos' => round($rem, 2),
                'no_remunerativos' => round($norem, 2),
                'descuentos' => round($desc, 2),
                'neto' => round($rem - $desc + $norem, 2),
            ];
            $detallePorCuil[(string) $cuil] = [
                'conceptos' => $conceptos,
                'bases' => $basesPorCuil[$cuil] ?? null,
            ];
        }

        // ---- Resumen de liquidación (totales por concepto + contadores de registros) ----
        // Importe con signo: crédito (+) suma, débito (−) resta. Período actual vs ajuste de otros períodos.
        $conceptosTot = [];
        foreach ($items as $it) {
            $cod = (string) $it->codigo_concepto;
            $monto = (($it->debito_credito === 'C') ? 1 : -1) * abs((float) $it->importe);
            $esOtroPeriodo = trim((string) ($it->periodo_ajuste ?? '')) !== '';
            if (!isset($conceptosTot[$cod])) {
                $conceptosTot[$cod] = ['codigo' => $cod, 'descripcion' => $descConcepto[$it->codigo_concepto] ?? '', 'total' => 0.0, 'actual' => 0.0, 'otros' => 0.0];
            }
            $conceptosTot[$cod]['total'] += $monto;
            $conceptosTot[$cod][$esOtroPeriodo ? 'otros' : 'actual'] += $monto;
        }
        uksort($conceptosTot, fn($a, $b) => strcmp($a, $b)); // orden por código como string (1, 102, 11, 17, ...)

        $regs = $this->contarRegistros($emision->archivo_txt);
        $resumenLiq = [
            'cuit' => $emision->cuit_empresa,
            'razon_social' => $empresa->detalle ?? '',
            'periodo' => $emision->periodo,
            'liquidacion' => ($regs['letra'] !== '' ? $regs['letra'] : '?') . '-' . $emision->numero_emision,
            'cant_trabajadores' => $items->pluck('cuil')->unique()->count(),
            'cant_eventuales' => $regs['counts']['05'],
            'cant_conceptos' => count($conceptosTot),
            'reg01' => $regs['counts']['01'],
            'reg02' => $regs['counts']['02'],
            'reg03' => $regs['counts']['03'],
            'reg04' => $regs['counts']['04'],
            'reg05' => $regs['counts']['05'],
            'conceptos' => array_values($conceptosTot),
        ];

        return Inertia::render('Lsd/Detalle', [
            'emision' => $emision,
            'empresa' => $empresa,
            'resumen' => $resumen,
            'detallePorCuil' => $detallePorCuil,
            'resumenLiq' => $resumenLiq,
        ]);
    }

    /**
     * Cuenta los registros del TXT por tipo (01-05) y devuelve la letra de tipo de liquidación del Reg 01.
     */
    private function contarRegistros(?string $path): array
    {
        $res = ['counts' => ['01' => 0, '02' => 0, '03' => 0, '04' => 0, '05' => 0], 'letra' => ''];
        if (empty($path) || !is_file($path)) {
            return $res;
        }
        $fh = @fopen($path, 'r');
        if (!$fh) {
            return $res;
        }
        while (($l = fgets($fh)) !== false) {
            $t = substr($l, 0, 2);
            if (isset($res['counts'][$t])) {
                $res['counts'][$t]++;
            }
            if ($t === '01' && $res['letra'] === '') {
                $res['letra'] = trim(substr($l, 21, 1));
            }
        }
        fclose($fh);
        return $res;
    }

    /**
     * Parsea los registros tipo 04 del TXT generado y devuelve, por CUIL, las bases imponibles y
     * códigos SICOSS (los cálculos que el LSD efectivamente informó). Numéricos: 2 decimales implícitos.
     */
    private function parsearReg04(?string $path): array
    {
        if (empty($path) || !is_file($path)) {
            return [];
        }
        $fh = @fopen($path, 'r');
        if (!$fh) {
            return [];
        }
        $num = fn(string $l, int $desde, int $largo): float => round(((int) substr($l, $desde - 1, $largo)) / 100, 2);
        $mapa = [];
        while (($linea = fgets($fh)) !== false) {
            $linea = rtrim($linea, "\r\n");
            if (substr($linea, 0, 2) !== '04') {
                continue;
            }
            $cuil = substr($linea, 2, 11);
            $mapa[$cuil] = [
                'situacion' => substr($linea, 21, 2),
                'condicion' => substr($linea, 23, 2),
                'actividad' => substr($linea, 25, 3),
                'modalidad' => substr($linea, 28, 3),
                'siniestro' => substr($linea, 31, 2),
                'localidad' => substr($linea, 33, 2),
                'dif_aportes_os' => $num($linea, 101, 15),
                'dif_contrib_os' => $num($linea, 116, 15),
                'dif_lrt' => $num($linea, 131, 15),
                'rem_maternidad' => $num($linea, 146, 15),
                'rem_bruta' => $num($linea, 161, 15),
                'bi1' => $num($linea, 176, 15),
                'bi2' => $num($linea, 191, 15),
                'bi3' => $num($linea, 206, 15),
                'bi4' => $num($linea, 221, 15),
                'bi5' => $num($linea, 236, 15),
                'bi6' => $num($linea, 251, 15),
                'bi7' => $num($linea, 266, 15),
                'bi8' => $num($linea, 281, 15),
                'bi9' => $num($linea, 296, 15),
                'dif_aporte_ss' => $num($linea, 311, 15),
                'dif_contrib_ss' => $num($linea, 326, 15),
                'bi10' => $num($linea, 341, 15),
                'importe_detraer' => $num($linea, 356, 15),
            ];
        }
        fclose($fh);
        return $mapa;
    }

    /**
     * Descargar el archivo TXT de una emisión ya generada
     */
    public function download($id)
    {
        $emision = LsdEmision::find($id);

        if (!$emision) {
            abort(404, 'Emisión no encontrada');
        }

        $filepath = $emision->archivo_txt;

        if (empty($filepath) || !is_file($filepath)) {
            abort(404, 'Archivo de emisión no encontrado');
        }

        return response()->download($filepath, basename($filepath));
    }

    public function obtenerEmision($id)
    {
        $emision = LsdEmision::with(['empresa', 'usuario'])->find($id);

        if (!$emision) {
            return response()->json(['error' => 'Emisión no encontrada'], 404);
        }

        return response()->json($emision);
    }

    /**
     * Transiciones permitidas entre estados de emisión.
     * Las hojas terminales (confirmado, rechazado) no permiten salir.
     */
    private const TRANSICIONES_PERMITIDAS = [
        'borrador'   => ['enviado'],
        'generado'   => ['enviado'],   // 'generado' se comporta como borrador a efectos del flujo
        'enviado'    => ['confirmado', 'rechazado'],
        'confirmado' => [],
        'rechazado'  => [],
    ];

    /**
     * Actualizar estado de emisión validando que la transición sea legal.
     */
    public function actualizarEstado($id, Request $request)
    {
        $request->validate([
            'estado' => 'required|in:borrador,generado,enviado,confirmado,rechazado',
        ]);

        $emision = LsdEmision::find($id);

        if (!$emision) {
            return response()->json(['success' => false, 'message' => 'Emisión no encontrada'], 404);
        }

        $estadoActual = $emision->estado;
        $nuevoEstado = $request->estado;

        if ($estadoActual === $nuevoEstado) {
            return response()->json([
                'success' => false,
                'message' => "La emisión ya está en estado \"{$estadoActual}\".",
            ], 422);
        }

        $permitidas = self::TRANSICIONES_PERMITIDAS[$estadoActual] ?? [];
        if (!in_array($nuevoEstado, $permitidas, true)) {
            return response()->json([
                'success' => false,
                'message' => "Transición no permitida: \"{$estadoActual}\" → \"{$nuevoEstado}\". " .
                    ($permitidas
                        ? 'Estados válidos desde aquí: ' . implode(', ', $permitidas) . '.'
                        : 'Este estado es final y no admite cambios.'),
            ], 422);
        }

        $updates = ['estado' => $nuevoEstado];
        if ($nuevoEstado === 'enviado') {
            $updates['fecha_envio'] = now();
        }

        $emision->update($updates);

        return response()->json([
            'success' => true,
            'message' => "Estado actualizado a \"{$nuevoEstado}\".",
            'emision' => $emision->fresh(),
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

    /**
     * Genera en el catálogo (sue102s) los conceptos "sin parametrizar" detectados
     * en el LSD. Para cada código:
     *   - tipo            ← tiporem del rango de sue089s que lo contiene (vacío si no cae en ninguno)
     *   - concepto_arca   ← codigo_afip del conceptosarca cuyo codigo_contribuyente coincide (null si no hay)
     * Omite los códigos que ya existen en sue102s.
     */
    public function generarConceptos(Request $request)
    {
        $datos = $request->validate([
            'conceptos'               => 'required|array|min:1',
            'conceptos.*.concepto'    => 'required',
            'conceptos.*.descripcion' => 'nullable|string',
        ]);

        $rangos = DB::table('sue089s')->get();

        // tiporem del rango que contiene al código (o '' si no cae en ninguno)
        $tipoPorCodigo = function ($codigo) use ($rangos): string {
            foreach ($rangos as $r) {
                if ($codigo >= $r->desde && $codigo <= $r->hasta) {
                    return trim($r->tiporem ?? '');
                }
            }
            return '';
        };

        $creados = 0;
        $omitidos = 0;
        $sinTipo = 0;
        $sinArca = 0;

        DB::transaction(function () use ($datos, $tipoPorCodigo, &$creados, &$omitidos, &$sinTipo, &$sinArca) {
            foreach ($datos['conceptos'] as $row) {
                $codigo = (int) $row['concepto'];

                if (Sue102::where('codigo', $codigo)->exists()) {
                    $omitidos++;
                    continue;
                }

                $tipo = $tipoPorCodigo($codigo);
                if ($tipo === '') {
                    $sinTipo++;
                }

                $codigoAfip = DB::table('conceptosarcas')
                    ->where('codigo_contribuyente', $codigo)
                    ->value('codigo_afip');
                if ($codigoAfip === null) {
                    $sinArca++;
                }

                $detalle = trim($row['descripcion'] ?? '') ?: "Concepto {$codigo}";

                Sue102::create([
                    'codigo'        => $codigo,
                    'detalle'       => mb_substr($detalle, 0, 250),
                    'tipo'          => $tipo,
                    'concepto_arca' => $codigoAfip !== null ? mb_substr((string) $codigoAfip, 0, 6) : null,
                ]);

                $creados++;
            }
        });

        return response()->json([
            'success'  => true,
            'creados'  => $creados,
            'omitidos' => $omitidos,
            'sin_tipo' => $sinTipo,
            'sin_arca' => $sinArca,
            'message'  => "Se generaron {$creados} conceptos ({$omitidos} ya existían).",
        ]);
    }
}
