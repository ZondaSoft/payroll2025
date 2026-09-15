<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\RepositorioBasesZonda;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Dominio\Entidades\BaseZonda;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Servicios\DetectorCircularidad;
use App\Motor\Dominio\ValorObjetos\Codigo;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseZonda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Bases de cálculo propias de Zonda: identidad + composición (qué conceptos la integran) + consumidores, con anticircularidad R13. */
class BasesZondaController extends Controller
{
    public function __construct(private readonly RepositorioBasesZonda $bases, private readonly RepositorioConceptos $conceptos) {}

    public function index(): Response
    {
        $hoy = now()->toDateTimeImmutable();
        $composicion = $this->bases->composicionVigente($hoy);
        $consumidores = [];
        foreach ($this->conceptos->todos(true) as $c) {
            if ($c->codigoBaseZonda !== null) {
                $consumidores[$c->codigoBaseZonda][] = (string) $c->codigo;
            }
        }
        $items = array_map(fn (BaseZonda $b) => [
            'id' => $b->id, 'codigo' => $b->codigo, 'nombre' => $b->nombre, 'descripcion' => $b->descripcion, 'activo' => $b->activo, 'estado_validacion' => $b->estadoValidacion->value,
            'integrantes' => array_values(array_map(fn ($i) => $i->codigoConcepto, array_filter($composicion[$b->codigo] ?? [], fn ($i) => $i->integra))),
            'consumidores' => $consumidores[$b->codigo] ?? [],
        ], $this->bases->todas());
        $integrantes = [];
        foreach ($composicion as $base => $lista) {
            $integrantes[$base] = array_map(fn ($i) => $i->codigoConcepto, array_filter($lista, fn ($i) => $i->integra));
        }
        $consumos = [];
        foreach ($this->conceptos->todos(true) as $c) {
            if ($c->codigoBaseZonda !== null) {
                $consumos[(string) $c->codigo] = $c->codigoBaseZonda;
            }
        }
        $det = new DetectorCircularidad;

        return Inertia::render('Motor/BasesZonda/Index', ['items' => $items, 'circularidades' => ['directas' => $det->directas($integrantes, $consumos), 'ciclos' => $det->ciclos($integrantes, $consumos)]]);
    }

    public function create(): Response
    {
        return $this->formulario(null);
    }

    public function edit(int $base): Response
    {
        $b = null;
        foreach ($this->bases->todas() as $x) {
            if ($x->id === $base) {
                $b = $x;
            }
        }

        return $this->formulario($b ?? abort(404));
    }

    public function store(Request $request): RedirectResponse
    {
        $b = $this->bases->guardar($this->desdeRequest($request, null), auth()->id());
        $this->guardarComposicion($request, $b);

        return redirect()->route('motor.bases-zonda.index')->with('success', "Base {$b->codigo} creada.");
    }

    public function update(Request $request, int $base): RedirectResponse
    {
        $actual = MotorBaseZonda::query()->findOrFail($base);
        $b = $this->bases->guardar($this->desdeRequest($request, $actual), auth()->id());
        $this->guardarComposicion($request, $b);

        return redirect()->route('motor.bases-zonda.index')->with('success', "Base {$b->codigo} actualizada.");
    }

    public function destroy(int $base): RedirectResponse
    {
        MotorBaseZonda::query()->whereKey($base)->update(['activo' => 0]);

        return redirect()->route('motor.bases-zonda.index')->with('success', 'Base dada de baja (baja lógica: su composición histórica se conserva).');
    }

    private function formulario(?BaseZonda $b): Response
    {
        $composicion = $b === null ? [] : array_map(fn (IncidenciaBaseZonda $i) => ['id' => $i->id, 'codigo_concepto' => $i->codigoConcepto, 'integra' => $i->integra, 'desde' => $i->vigencia->desde->format('Y-m-d'), 'hasta' => $i->vigencia->hasta?->format('Y-m-d'), 'estado' => $i->estadoValidacion->value],
            array_values(array_filter($this->bases->composicionVigente(now()->toDateTimeImmutable())[$b->codigo] ?? [], fn () => true)));
        $conceptos = array_map(fn ($c) => ['codigo' => (string) $c->codigo, 'nombre' => $c->nombre, 'naturaleza' => $c->naturaleza->value, 'tipo' => $c->tipo->value, 'consume' => $c->codigoBaseZonda], $this->conceptos->todos(true));

        return Inertia::render('Motor/BasesZonda/Form', [
            'base' => $b === null ? null : ['id' => $b->id, 'codigo' => $b->codigo, 'nombre' => $b->nombre, 'descripcion' => $b->descripcion, 'activo' => $b->activo, 'estado_validacion' => $b->estadoValidacion->value],
            'composicion' => $composicion, 'conceptos' => $conceptos,
        ]);
    }

    private function desdeRequest(Request $request, ?MotorBaseZonda $actual): BaseZonda
    {
        $d = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:motor_bases_zonda,codigo_base'.($actual !== null ? ",{$actual->id_base_zonda},id_base_zonda" : '')],
            'nombre' => 'required|string|max:100', 'descripcion' => 'nullable|string', 'activo' => 'boolean', 'estado_validacion' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
        ], ['codigo.required' => 'El código es obligatorio.', 'codigo.regex' => 'El código solo admite letras, números y guión bajo.', 'codigo.unique' => 'Ya existe una base con ese código.', 'nombre.required' => 'El nombre es obligatorio.']);

        return new BaseZonda($actual?->id_base_zonda, (string) Codigo::de($d['codigo']), $d['nombre'], $d['descripcion'] ?? null, (bool) ($d['activo'] ?? true), EstadoValidacion::from($d['estado_validacion']));
    }

    private function guardarComposicion(Request $request, BaseZonda $b): void
    {
        $existentes = [];
        foreach ($this->bases->composicionVigente(now()->toDateTimeImmutable())[$b->codigo] ?? [] as $i) {
            $existentes[$i->codigoConcepto] = $i;
        }
        foreach ((array) $request->input('composicion', []) as $fila) {
            $codigo = strtoupper((string) ($fila['codigo_concepto'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $integra = filter_var($fila['integra'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $prev = $existentes[$codigo] ?? null;
            $vig = Vigencia::de($fila['desde'] ?? now()->format('Y-m-01'), $fila['hasta'] ?? null);
            if ($prev !== null && $prev->integra === $integra && (string) $prev->vigencia === (string) $vig) {
                continue;
            }
            $this->bases->guardarIncidencia(new IncidenciaBaseZonda($prev?->id, $b->id, $b->codigo, $codigo, $integra, $vig, EstadoValidacion::SinConfirmar), auth()->id());
        }
    }
}
