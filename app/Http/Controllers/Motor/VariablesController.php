<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\RepositorioVariables;
use App\Motor\Dominio\Entidades\ValorVariable;
use App\Motor\Dominio\Entidades\Variable;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Formulas\Dsl;
use App\Motor\Dominio\Formulas\ResolvedorDominio;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use Brick\Math\BigDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Variables del DSL y sus valores por convenio.
 *
 * Es la pantalla que faltaba para poner un convenio nuevo en marcha: sin DIVJOR y
 * HMJORN cargados no hay feriado trabajado ni hora extra que se pueda calcular, y
 * hasta ahora sólo se cargaban por seeder.
 *
 * Las que resuelve el contexto del trabajador (BASCAT de la escala de básicos,
 * HSPACT de la jornada pactada, DIASLIQ y DIASEFE de la liquidación) no admiten
 * valor cargado: se marcan y el formulario lo dice en vez de dejar cargar algo que
 * el motor nunca va a leer.
 */
class VariablesController extends Controller
{
    public function __construct(private readonly RepositorioVariables $variables) {}

    public function index(): Response
    {
        $vigentes = [];
        foreach ($this->variables->valoresVigentes(new \DateTimeImmutable) as $v) {
            $vigentes[$v->codigo] = ($vigentes[$v->codigo] ?? 0) + 1;
        }

        return Inertia::render('Motor/Variables/Index', [
            'items' => array_map(fn (Variable $v) => [
                'codigo' => $v->codigo,
                'descripcion' => $v->descripcion,
                'unidad' => $v->unidad,
                'origen' => $v->origen,
                'activo' => $v->activo,
                'se_carga' => $v->seCarga(),
                'valores' => count($this->variables->valoresDe($v->codigo)),
                'vigentes' => $vigentes[$v->codigo] ?? 0,
            ], $this->variables->catalogo()),
            'del_contexto' => ResolvedorDominio::DEL_CONTEXTO,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Variables/Form', ['variable' => null, 'valores' => [], 'catalogos' => $this->catalogos()]);
    }

    public function edit(string $variable): Response
    {
        $v = $this->variables->porCodigo($variable);
        abort_if($v === null, 404);

        return Inertia::render('Motor/Variables/Form', [
            'variable' => [
                'codigo' => $v->codigo, 'descripcion' => $v->descripcion, 'unidad' => $v->unidad,
                'origen' => $v->origen, 'activo' => $v->activo, 'observaciones' => $v->observaciones,
                'se_carga' => $v->seCarga(), 'del_contexto' => in_array($v->codigo, ResolvedorDominio::DEL_CONTEXTO, true),
            ],
            'valores' => array_map(fn (ValorVariable $x) => [
                'id' => $x->id, 'alcance' => $x->alcance->value, 'id_convenio' => $x->idConvenio,
                'id_categoria' => $x->idCategoria, 'relacion_laboral_id' => $x->relacionLaboralId,
                'valor' => (string) $x->valor, 'desde' => $x->vigencia->desde->format('Y-m-d'),
                'hasta' => $x->vigencia->hasta?->format('Y-m-d'), 'estado' => $x->estadoValidacion->value,
                'fuente' => $x->fuente,
            ], $this->variables->valoresDe($variable)),
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request, null);
        $this->variables->guardar($this->desdeDatos($d), auth()->id());

        return redirect()->route('motor.variables.edit', $d['codigo'])
            ->with('success', 'Variable creada. Cargá sus valores por convenio.');
    }

    public function update(Request $request, string $variable): RedirectResponse
    {
        $actual = $this->variables->porCodigo($variable);
        abort_if($actual === null, 404);
        $d = $this->validar($request, $actual);
        $this->variables->guardar($this->desdeDatos($d + ['codigo' => $variable]), auth()->id());

        return redirect()->route('motor.variables.edit', $variable)->with('success', 'Variable actualizada.');
    }

    public function destroy(string $variable): RedirectResponse
    {
        $v = $this->variables->porCodigo($variable);
        abort_if($v === null, 404);
        // Baja lógica: una fórmula publicada puede estar usándola y el motor tiene que
        // poder explicar por qué dejó de resolver, no encontrarse con que no existe.
        $this->variables->guardar(new Variable($v->codigo, $v->descripcion, $v->unidad, $v->origen, false, $v->observaciones), auth()->id());

        return redirect()->route('motor.variables.index')->with('success', 'Variable dada de baja (baja lógica).');
    }

    public function guardarValor(Request $request, string $variable): JsonResponse
    {
        $v = $this->variables->porCodigo($variable);
        abort_if($v === null, 404);

        $d = $request->validate([
            'id' => 'nullable|integer',
            'alcance' => 'required|in:GENERAL,CONVENIO,CATEGORIA,TRABAJADOR',
            'id_convenio' => 'nullable|integer',
            'id_categoria' => 'nullable|integer',
            'relacion_laboral_id' => 'nullable|integer|exists:motor_relacion_laboral,relacion_laboral_id',
            'valor' => ['required', 'regex:/^-?\d{1,12}([.,]\d{1,6})?$/'],
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'estado' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
            'fuente' => 'nullable|string|max:255',
        ], [
            'valor.required' => 'El valor es obligatorio.',
            'valor.regex' => 'El valor debe ser numérico con punto decimal (hasta 6 decimales).',
            'desde.required' => 'La vigencia desde es obligatoria.',
            'hasta.after_or_equal' => 'RT-007: vigencia hasta no puede ser anterior a vigencia desde.',
        ]);

        if (in_array($v->codigo, ResolvedorDominio::DEL_CONTEXTO, true)) {
            return response()->json([
                'message' => "La variable {$v->codigo} la resuelve el contexto del trabajador (escala de básicos, jornada "
                    .'pactada o días de la liquidación). Un valor cargado acá no se leería nunca.',
            ], 422);
        }

        $alcance = AlcanceParametro::from($d['alcance']);
        $ambitoOk = match ($alcance) {
            AlcanceParametro::General => true,
            AlcanceParametro::Convenio => ! empty($d['id_convenio']),
            AlcanceParametro::Categoria => ! empty($d['id_categoria']),
            AlcanceParametro::Trabajador => ! empty($d['relacion_laboral_id']),
            default => false,
        };
        if (! $ambitoOk) {
            return response()->json(['message' => "El alcance {$alcance->value} exige indicar el ámbito correspondiente."], 422);
        }

        $vig = Vigencia::de($d['desde'], $d['hasta'] ?? null);
        foreach ($this->variables->valoresDe($variable) as $x) {
            if ($x->id === ($d['id'] ?? null) || $x->alcance !== $alcance) {
                continue;
            }
            $mismoAmbito = $x->idConvenio === ($alcance === AlcanceParametro::Convenio ? (int) $d['id_convenio'] : null)
                && $x->idCategoria === ($alcance === AlcanceParametro::Categoria ? (int) $d['id_categoria'] : null)
                && $x->relacionLaboralId === ($alcance === AlcanceParametro::Trabajador ? (int) $d['relacion_laboral_id'] : null);
            if ($mismoAmbito && $x->vigencia->seSuperponeCon($vig)) {
                return response()->json([
                    'message' => "RT-007: la vigencia se superpone con otro valor del mismo ámbito ({$x->vigencia}). "
                        .'Cerrá el anterior antes de abrir uno nuevo — dos valores vigentes en el mismo nivel bloquean el cálculo.',
                ], 422);
            }
        }

        $guardado = $this->variables->guardarValor(new ValorVariable(
            $d['id'] ?? null,
            $v->codigo,
            $alcance,
            $alcance === AlcanceParametro::Convenio ? (int) $d['id_convenio'] : null,
            $alcance === AlcanceParametro::Categoria ? (int) $d['id_categoria'] : null,
            $alcance === AlcanceParametro::Trabajador ? (int) $d['relacion_laboral_id'] : null,
            BigDecimal::of(str_replace(',', '.', $d['valor'])),
            $vig,
            EstadoValidacion::from($d['estado']),
            $d['fuente'] ?? null,
        ), auth()->id());

        return response()->json(['success' => true, 'id' => $guardado->id]);
    }

    public function eliminarValor(string $variable, int $valor): JsonResponse
    {
        $this->variables->eliminarValor($variable, $valor);

        return response()->json(['success' => true]);
    }

    /** @param array<string, mixed> $d */
    private function desdeDatos(array $d): Variable
    {
        return new Variable(
            strtoupper($d['codigo']), $d['descripcion'], $d['unidad'] ?: null,
            $d['origen'], (bool) ($d['activo'] ?? true), $d['observaciones'] ?: null,
        );
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Variable $actual): array
    {
        $largo = Dsl::MAX_LARGO_IDENT;

        return $request->validate([
            'codigo' => array_filter([
                $actual !== null ? 'nullable' : 'required',
                'string', "max:{$largo}", 'regex:/^[A-Za-z0-9_]+$/',
                $actual === null ? 'unique:motor_variables,codigo' : null,
            ]),
            'descripcion' => 'required|string|max:150',
            'unidad' => 'nullable|string|max:20',
            'origen' => 'required|in:CATEGORIA,RELACION_LABORAL,CONVENIO,PERIODO',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => "El DSL limita los códigos de VAR() a {$largo} caracteres (§4).",
            'codigo.regex' => 'El código solo admite letras, números y guión bajo.',
            'codigo.unique' => 'Ya existe una variable con ese código.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'origen.required' => 'El origen decide quién resuelve la variable.',
        ]);
    }

    /** @return array<string, mixed> */
    private function catalogos(): array
    {
        return [
            // CATEGORIA y RELACION_LABORAL no se cargan: las resuelve el contexto. Se
            // ofrecen igual porque el catálogo las declara y hay que poder verlas.
            'origenes' => [
                ['value' => 'CONVENIO', 'label' => 'Convenio — se carga acá (divisores normativos)'],
                ['value' => 'PERIODO', 'label' => 'Período — la resuelve la liquidación'],
                ['value' => 'CATEGORIA', 'label' => 'Categoría — la resuelve la escala de básicos'],
                ['value' => 'RELACION_LABORAL', 'label' => 'Relación laboral — la resuelve la jornada del trabajador'],
            ],
            'alcances' => [
                ['value' => 'GENERAL', 'label' => 'General'],
                ['value' => 'CONVENIO', 'label' => 'Convenio'],
                ['value' => 'CATEGORIA', 'label' => 'Categoría'],
                ['value' => 'TRABAJADOR', 'label' => 'Trabajador'],
            ],
            'unidades' => array_map(fn ($u) => ['value' => $u, 'label' => $u], ['ARS', 'HORAS', 'DIAS', 'KM', 'UNIDADES', 'NUMERIC', 'PORCENTAJE']),
            'convenios' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorConvenios::class)->todos()),
            'categorias' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorCategorias::class)->todas()),
            'relaciones' => MotorRelacionLaboral::query()->orderBy('legajo')->get(['relacion_laboral_id', 'legajo', 'apellido_nombre'])
                ->map(fn ($r) => ['value' => $r->relacion_laboral_id, 'label' => "{$r->legajo} — {$r->apellido_nombre}"])->all(),
        ];
    }
}
