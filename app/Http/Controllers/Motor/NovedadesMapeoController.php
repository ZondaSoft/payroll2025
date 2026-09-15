<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorNovedadesOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioIfNovedadOrigen;
use App\Motor\Aplicacion\Puertos\RepositorioTiposNovedad;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorNovedadMapeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Mapeo sue031s (cod_nov × columna) → tipo de novedad del motor, con la lista de códigos usados en sue028s sin mapear. */
class NovedadesMapeoController extends Controller
{
    public const COLUMNAS = ['cantidad', 'int_vac', 'int_enf', 'int_acc', 'int_susp', 'int_aperc', 'int_licencias'];

    public function __construct(private readonly RepositorioTiposNovedad $tipos, private readonly ProveedorNovedadesOrigen $origen, private readonly RepositorioIfNovedadOrigen $ifNovedades) {}

    public function index(): Response
    {
        $mapeos = MotorNovedadMapeo::query()->orderBy('cod_nov')->orderBy('columna_origen')->get()->map(fn ($m) => ['id' => $m->id_mapeo, 'cod_nov' => $m->cod_nov, 'columna' => $m->columna_origen, 'tipo' => $m->codigo_tipo_novedad, 'unidad' => $m->unidad, 'es_importe' => (bool) $m->es_importe, 'factor' => $m->factor, 'accion' => $m->accion, 'activo' => (bool) $m->activo])->all();
        $mapeados = [];
        foreach ($mapeos as $m) {
            $mapeados[$m['cod_nov']] = true;
        }
        $catalogo = $this->origen->catalogoLegacy();
        $usados = $this->origen->codigosUsados();
        $sinMapear = [];
        foreach ($usados as $cod => $n) {
            if (! isset($mapeados[$cod])) {
                $sinMapear[] = ['cod_nov' => $cod, 'detalle' => $catalogo[$cod] ?? '(no está en sue031s)', 'usos' => $n];
            }
        }

        return Inertia::render('Motor/NovedadesMapeo/Index', ['mapeos' => $mapeos, 'sin_mapear' => $sinMapear, 'resumen_if' => $this->ifNovedades->resumenEstados(),
            'tipos' => array_map(fn ($t) => ['value' => $t->codigo, 'label' => "{$t->codigo} — {$t->descripcion} ({$t->unidad})", 'unidad' => $t->unidad], $this->tipos->todos()),
            'columnas' => self::COLUMNAS, 'catalogo_legacy' => array_map(fn ($cod, $det) => ['value' => $cod, 'label' => "{$cod} — {$det}", 'usos' => $usados[$cod] ?? 0], array_keys($catalogo), $catalogo)]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Motor/NovedadesMapeo/Form', ['item' => null, 'preseleccion' => $request->only(['cod_nov']), 'tipos' => $this->opcionesTipos(), 'columnas' => self::COLUMNAS, 'catalogo_legacy' => $this->catalogoOpciones()]);
    }

    public function edit(int $mapeo): Response
    {
        return Inertia::render('Motor/NovedadesMapeo/Form', ['item' => MotorNovedadMapeo::query()->findOrFail($mapeo), 'preseleccion' => [], 'tipos' => $this->opcionesTipos(), 'columnas' => self::COLUMNAS, 'catalogo_legacy' => $this->catalogoOpciones()]);
    }

    public function store(Request $request): RedirectResponse
    {
        MotorNovedadMapeo::query()->create($this->validar($request, null) + ['usuario_id' => auth()->id()]);

        return redirect()->route('motor.novedades-mapeo.index')->with('success', 'Mapeo creado. Volvé a normalizar el período para aplicarlo.');
    }

    public function update(Request $request, int $mapeo): RedirectResponse
    {
        $m = MotorNovedadMapeo::query()->findOrFail($mapeo);
        $m->update($this->validar($request, $m));

        return redirect()->route('motor.novedades-mapeo.index')->with('success', 'Mapeo actualizado.');
    }

    public function destroy(int $mapeo): RedirectResponse
    {
        MotorNovedadMapeo::query()->whereKey($mapeo)->update(['activo' => 0]);

        return redirect()->route('motor.novedades-mapeo.index')->with('success', 'Mapeo desactivado.');
    }

    private function validar(Request $request, ?MotorNovedadMapeo $actual): array
    {
        $d = $request->validate([
            'cod_nov' => 'required|string|max:10', 'columna_origen' => 'required|in:'.implode(',', self::COLUMNAS), 'codigo_tipo_novedad' => 'required|string|exists:motor_tipos_novedad,codigo', 'unidad' => 'nullable|string|max:20',
            'es_importe' => 'boolean', 'factor' => ['nullable', 'regex:/^-?\d{1,12}([.,]\d{1,6})?$/'], 'accion' => 'required|in:NORMALIZAR,IGNORAR', 'activo' => 'boolean', 'observaciones' => 'nullable|string',
        ], ['cod_nov.required' => 'Indicá el código de novedad legacy.', 'codigo_tipo_novedad.required' => 'Elegí el tipo de novedad del motor.', 'codigo_tipo_novedad.exists' => 'El tipo de novedad no existe.']);
        $q = MotorNovedadMapeo::query()->where('cod_nov', $d['cod_nov'])->where('columna_origen', $d['columna_origen']);
        if ($actual !== null) {
            $q->whereKeyNot($actual->id_mapeo);
        }
        if ($q->exists()) {
            abort(422, 'Ya existe un mapeo para ese código y columna.');
        }
        $d['factor'] = str_replace(',', '.', (string) ($d['factor'] ?? '1')) ?: '1';
        $d['es_importe'] = (bool) ($d['es_importe'] ?? false);
        $d['activo'] = (bool) ($d['activo'] ?? true);

        return $d;
    }

    private function opcionesTipos(): array
    {
        return array_map(fn ($t) => ['value' => $t->codigo, 'label' => "{$t->codigo} — {$t->descripcion} ({$t->unidad})", 'unidad' => $t->unidad], $this->tipos->todos());
    }

    private function catalogoOpciones(): array
    {
        $usados = $this->origen->codigosUsados();
        $out = [];
        foreach ($this->origen->catalogoLegacy() as $cod => $det) {
            $out[] = ['value' => $cod, 'label' => "{$cod} — {$det}".(isset($usados[$cod]) ? " ({$usados[$cod]} usos)" : '')];
        }

        return $out;
    }
}
