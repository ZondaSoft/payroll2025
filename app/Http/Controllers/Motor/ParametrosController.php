<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\RepositorioParametros;
use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorParametro;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorValorParametro;
use Brick\Math\BigDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Parámetros del motor con sus valores por alcance y vigencia (edición inline de valores). */
class ParametrosController extends Controller
{
    public function __construct(private readonly RepositorioParametros $parametros) {}

    public function index(Request $request): Response
    {
        $q = MotorParametro::query()->withCount('valores')->orderBy('es_tecnico')->orderBy('codigo');
        if ($request->filled('buscar')) {
            $b = '%'.$request->input('buscar').'%';
            $q->where(fn ($w) => $w->where('codigo', 'like', $b)->orWhere('descripcion', 'like', $b));
        }
        $usados = \Illuminate\Support\Facades\DB::table('motor_conceptos')->whereNotNull('codigo_parametro')->selectRaw('codigo_parametro, count(*) n')->groupBy('codigo_parametro')->pluck('n', 'codigo_parametro');

        return Inertia::render('Motor/Parametros/Index', ['items' => $q->paginate(20)->withQueryString()->through(fn ($p) => [
            'id' => $p->id_parametro, 'codigo' => $p->codigo, 'descripcion' => $p->descripcion, 'tipo' => $p->tipo_parametro, 'unidad' => $p->unidad, 'es_tecnico' => (bool) $p->es_tecnico, 'activo' => (bool) $p->activo,
            'valores' => $p->valores_count, 'conceptos' => (int) ($usados[$p->codigo] ?? 0),
        ]), 'filtros' => $request->only(['buscar'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Parametros/Form', ['parametro' => null, 'valores' => [], 'catalogos' => $this->catalogos()]);
    }

    public function edit(int $parametro): Response
    {
        $p = MotorParametro::query()->findOrFail($parametro);

        return Inertia::render('Motor/Parametros/Form', [
            'parametro' => ['id' => $p->id_parametro, 'codigo' => $p->codigo, 'descripcion' => $p->descripcion, 'tipo_parametro' => $p->tipo_parametro, 'unidad' => $p->unidad, 'es_tecnico' => (bool) $p->es_tecnico, 'activo' => (bool) $p->activo],
            'valores' => array_map(fn (ValorParametro $v) => ['id' => $v->id, 'alcance' => $v->alcance->value, 'id_convenio' => $v->idConvenio, 'id_categoria' => $v->idCategoria, 'id_empresa' => $v->idEmpresa, 'relacion_laboral_id' => $v->relacionLaboralId,
                'valor' => (string) $v->valor, 'desde' => $v->vigencia->desde->format('Y-m-d'), 'hasta' => $v->vigencia->hasta?->format('Y-m-d'), 'estado' => $v->estadoValidacion->value], $this->parametros->valoresDe($parametro)),
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request, null);
        $p = MotorParametro::query()->create($d + ['usuario_id' => auth()->id()]);

        return redirect()->route('motor.parametros.edit', $p->id_parametro)->with('success', 'Parámetro creado. Cargá sus valores por alcance.');
    }

    public function update(Request $request, int $parametro): RedirectResponse
    {
        $p = MotorParametro::query()->findOrFail($parametro);
        $d = $this->validar($request, $p);
        unset($d['codigo']);
        $p->update($d);

        return redirect()->route('motor.parametros.edit', $p->id_parametro)->with('success', 'Parámetro actualizado.');
    }

    public function destroy(int $parametro): RedirectResponse
    {
        MotorParametro::query()->whereKey($parametro)->update(['activo' => 0]);

        return redirect()->route('motor.parametros.index')->with('success', 'Parámetro dado de baja (baja lógica).');
    }

    public function guardarValor(Request $request, int $parametro): JsonResponse
    {
        $p = MotorParametro::query()->findOrFail($parametro);
        $d = $request->validate([
            'id' => 'nullable|integer', 'alcance' => 'required|in:GENERAL,CONVENIO,CATEGORIA,EMPRESA,TRABAJADOR',
            'id_convenio' => 'nullable|integer', 'id_categoria' => 'nullable|integer', 'id_empresa' => 'nullable|integer', 'relacion_laboral_id' => 'nullable|integer|exists:motor_relacion_laboral,relacion_laboral_id',
            'valor' => ['required', 'regex:/^-?\d{1,12}([.,]\d{1,6})?$/'], 'desde' => 'required|date_format:Y-m-d', 'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde', 'estado' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
        ], ['valor.required' => 'El valor es obligatorio.', 'valor.regex' => 'El valor debe ser numérico con punto decimal (hasta 6 decimales).', 'desde.required' => 'La vigencia desde es obligatoria.', 'hasta.after_or_equal' => 'RT-007: vigencia hasta no puede ser anterior a vigencia desde.']);
        $alcance = AlcanceParametro::from($d['alcance']);
        $ambitoOk = match ($alcance) {
            AlcanceParametro::General => true, AlcanceParametro::Convenio => ! empty($d['id_convenio']), AlcanceParametro::Categoria => ! empty($d['id_categoria']),
            AlcanceParametro::Empresa => ! empty($d['id_empresa']), AlcanceParametro::Trabajador => ! empty($d['relacion_laboral_id']),
        };
        if (! $ambitoOk) {
            return response()->json(['message' => "El alcance {$alcance->value} exige indicar el ámbito correspondiente."], 422);
        }
        $vig = Vigencia::de($d['desde'], $d['hasta'] ?? null);
        // RT-007: sin superposición dentro del mismo ámbito
        foreach ($this->parametros->valoresDe($parametro) as $v) {
            if ($v->id === ($d['id'] ?? null) || $v->alcance !== $alcance) {
                continue;
            }
            $mismoAmbito = $v->idConvenio === ($alcance === AlcanceParametro::Convenio ? (int) $d['id_convenio'] : null) && $v->idCategoria === ($alcance === AlcanceParametro::Categoria ? (int) $d['id_categoria'] : null)
                && $v->idEmpresa === ($alcance === AlcanceParametro::Empresa ? (int) $d['id_empresa'] : null) && $v->relacionLaboralId === ($alcance === AlcanceParametro::Trabajador ? (int) $d['relacion_laboral_id'] : null);
            if ($mismoAmbito && $v->vigencia->seSuperponeCon($vig)) {
                return response()->json(['message' => "RT-007: la vigencia se superpone con otro valor del mismo ámbito ({$v->vigencia}). Cerrá el anterior antes de abrir uno nuevo."], 422);
            }
        }
        $v = $this->parametros->guardarValor(new ValorParametro($d['id'] ?? null, $p->id_parametro, $p->codigo, $alcance,
            $alcance === AlcanceParametro::Convenio ? (int) $d['id_convenio'] : null, $alcance === AlcanceParametro::Categoria ? (int) $d['id_categoria'] : null,
            $alcance === AlcanceParametro::Empresa ? (int) $d['id_empresa'] : null, $alcance === AlcanceParametro::Trabajador ? (int) $d['relacion_laboral_id'] : null,
            BigDecimal::of(str_replace(',', '.', $d['valor'])), $vig, EstadoValidacion::from($d['estado'])), auth()->id());

        return response()->json(['success' => true, 'id' => $v->id]);
    }

    public function eliminarValor(int $parametro, int $valor): JsonResponse
    {
        MotorValorParametro::query()->where('id_parametro', $parametro)->whereKey($valor)->delete();

        return response()->json(['success' => true]);
    }

    private function validar(Request $request, ?MotorParametro $actual): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:motor_parametros,codigo'.($actual !== null ? ",{$actual->id_parametro},id_parametro" : '')],
            'descripcion' => 'required|string|max:255', 'tipo_parametro' => 'required|in:PORCENTAJE,TARIFA,HORAS,CANTIDAD,ENTERO,IMPORTE', 'unidad' => 'nullable|string|max:30', 'es_tecnico' => 'boolean', 'activo' => 'boolean',
        ], ['codigo.required' => 'El código es obligatorio.', 'codigo.regex' => 'El código solo admite letras, números y guión bajo.', 'codigo.unique' => 'Ya existe un parámetro con ese código (RT-009: no se reutilizan códigos).', 'descripcion.required' => 'La descripción es obligatoria.']);
    }

    private function catalogos(): array
    {
        return [
            'alcances' => AlcanceParametro::opciones(), 'tipos' => array_map(fn ($t) => ['value' => $t, 'label' => $t], ['PORCENTAJE', 'TARIFA', 'HORAS', 'CANTIDAD', 'ENTERO', 'IMPORTE']),
            'convenios' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorConvenios::class)->todos()),
            'categorias' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorCategorias::class)->todas()),
            'empresas' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->razonSocial}"], app(ProveedorEmpresas::class)->todas()),
            'relaciones' => MotorRelacionLaboral::query()->orderBy('legajo')->get(['relacion_laboral_id', 'legajo', 'apellido_nombre'])->map(fn ($r) => ['value' => $r->relacion_laboral_id, 'label' => "{$r->legajo} — {$r->apellido_nombre}"])->all(),
        ];
    }
}
