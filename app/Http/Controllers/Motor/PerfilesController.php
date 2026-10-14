<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Aplicacion\Puertos\ProveedorEmpresas;
use App\Motor\Aplicacion\Puertos\RepositorioFormulas;
use App\Motor\Aplicacion\Puertos\RepositorioPerfiles;
use App\Motor\Dominio\Entidades\Perfil;
use App\Motor\Dominio\Entidades\PerfilConcepto;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Formulas\VersionFormula;
use App\Motor\Dominio\Servicios\ResolutorPerfiles;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorConcepto;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorRelacionLaboral;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Perfiles de liquidación: la matriz de conceptos con `aplica`, variante y motivo.
 *
 * Es la pantalla donde se decide **qué cobra cada quién y con qué fórmula**. Lo que
 * la hace distinta de la vieja pestaña de aplicabilidad es la variante: el mismo
 * BASICO se liquida con JORNADA_PACTADA en el 371/03 y con LARGA_DISTANCIA en el
 * 40/89, y eso lo dice el perfil, no el concepto.
 *
 * El formulario muestra las variantes publicadas de cada concepto para que no haya
 * que adivinarlas, y exige una cuando el concepto aplica: sin variante el motor
 * bloquea con ERROR_CONFIGURACION en vez de elegir la primera.
 */
class PerfilesController extends Controller
{
    public function __construct(
        private readonly RepositorioPerfiles $perfiles,
        private readonly RepositorioFormulas $formulas,
    ) {}

    public function index(): Response
    {
        $perfiles = $this->perfiles->todos();

        return Inertia::render('Motor/Perfiles/Index', [
            'items' => array_map(fn (Perfil $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'nivel' => $p->nivel->value,
                'precedencia' => $p->precedencia(),
                'ambito' => $this->ambito($p),
                'activo' => $p->activo,
                'desde' => $p->vigencia->desde->format('Y-m-d'),
                'hasta' => $p->vigencia->hasta?->format('Y-m-d'),
                'reglas' => count($p->conceptos),
                'incluye' => count(array_filter($p->conceptos, fn (PerfilConcepto $c) => $c->aplica)),
                'excluye' => count(array_filter($p->conceptos, fn (PerfilConcepto $c) => ! $c->aplica)),
            ], $perfiles),
            'precedencias' => Perfil::PRECEDENCIAS,
            'ambiguos' => $this->ambiguedades($perfiles),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Perfiles/Form', ['perfil' => null, 'reglas' => [], 'catalogos' => $this->catalogos()]);
    }

    public function edit(int $perfil): Response
    {
        $p = $this->perfiles->porId($perfil);
        abort_if($p === null, 404);

        return Inertia::render('Motor/Perfiles/Form', [
            'perfil' => [
                'id' => $p->id, 'codigo' => $p->codigo, 'nombre' => $p->nombre, 'nivel' => $p->nivel->value,
                'precedencia' => $p->precedencia(), 'id_convenio' => $p->idConvenio, 'id_categoria' => $p->idCategoria,
                'empresa_id' => $p->idEmpresa, 'relacion_laboral_id' => $p->relacionLaboralId,
                'desde' => $p->vigencia->desde->format('Y-m-d'), 'hasta' => $p->vigencia->hasta?->format('Y-m-d'),
                'activo' => $p->activo, 'observaciones' => $p->observaciones,
            ],
            'reglas' => array_map(fn (PerfilConcepto $c) => [
                'id' => $c->id, 'codigo_concepto' => $c->codigoConcepto, 'aplica' => $c->aplica,
                'variante' => $c->variante, 'motivo' => $c->motivo,
                'desde' => $c->vigencia->desde->format('Y-m-d'), 'hasta' => $c->vigencia->hasta?->format('Y-m-d'),
                'estado' => $c->estadoValidacion->value,
            ], $p->conceptos),
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request, null);
        $p = $this->perfiles->guardar($this->desdeDatos($d, null), auth()->id());

        return redirect()->route('motor.perfiles.edit', $p->id)
            ->with('success', 'Perfil creado. Cargá la matriz de conceptos con su variante.');
    }

    public function update(Request $request, int $perfil): RedirectResponse
    {
        $actual = $this->perfiles->porId($perfil);
        abort_if($actual === null, 404);
        $d = $this->validar($request, $actual);
        $this->perfiles->guardar($this->desdeDatos($d, $actual), auth()->id());

        return redirect()->route('motor.perfiles.edit', $perfil)->with('success', 'Perfil actualizado.');
    }

    public function destroy(int $perfil): RedirectResponse
    {
        $this->perfiles->eliminar($perfil);

        return redirect()->route('motor.perfiles.index')->with('success', 'Perfil dado de baja (baja lógica).');
    }

    public function guardarRegla(Request $request, int $perfil): JsonResponse
    {
        $p = $this->perfiles->porId($perfil);
        abort_if($p === null, 404);

        $d = $request->validate([
            'id' => 'nullable|integer',
            'codigo_concepto' => 'required|string|exists:motor_conceptos,codigo_concepto',
            'aplica' => 'required|boolean',
            'variante' => 'nullable|string|max:30',
            'motivo' => 'nullable|string|max:255',
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'estado' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO',
        ], [
            'codigo_concepto.exists' => 'El concepto no existe en el catálogo del motor.',
            'hasta.after_or_equal' => 'RT-007: vigencia hasta no puede ser anterior a vigencia desde.',
        ]);

        if ($d['aplica'] && trim((string) ($d['variante'] ?? '')) === '') {
            return response()->json([
                'message' => 'Un concepto que aplica necesita variante: es lo que elige la fórmula. Si el concepto no '
                    .'tiene variantes publicadas, poné UNICA.',
            ], 422);
        }
        if (! $d['aplica'] && trim((string) ($d['motivo'] ?? '')) === '') {
            return response()->json([
                'message' => 'Una exclusión necesita motivo: se copia al recibo para explicar por qué el concepto no está.',
            ], 422);
        }

        $variantes = $this->variantesPublicadas()[$d['codigo_concepto']] ?? [];
        if ($d['aplica'] && $variantes !== [] && ! in_array($d['variante'], $variantes, true)) {
            return response()->json([
                'message' => "El concepto {$d['codigo_concepto']} no tiene publicada la variante {$d['variante']}. "
                    .'Publicadas: '.implode(', ', $variantes).'.',
            ], 422);
        }

        $vig = Vigencia::de($d['desde'], $d['hasta'] ?? null);
        foreach ($p->conceptos as $c) {
            if ($c->id === ($d['id'] ?? null) || $c->codigoConcepto !== $d['codigo_concepto']) {
                continue;
            }
            if ($c->vigencia->seSuperponeCon($vig)) {
                return response()->json([
                    'message' => "RT-007: ya hay una regla para {$d['codigo_concepto']} en este perfil con vigencia "
                        ."superpuesta ({$c->vigencia}). Cerrá la anterior antes de abrir una nueva.",
                ], 422);
            }
        }

        $regla = $this->perfiles->guardarRegla($perfil, new PerfilConcepto(
            $d['id'] ?? null,
            $d['codigo_concepto'],
            (bool) $d['aplica'],
            $vig,
            $d['aplica'] ? $d['variante'] : null,
            $d['motivo'] ?: null,
            EstadoValidacion::from($d['estado']),
        ));

        return response()->json(['success' => true, 'id' => $regla->id]);
    }

    public function eliminarRegla(int $perfil, int $regla): JsonResponse
    {
        $this->perfiles->eliminarRegla($perfil, $regla);

        return response()->json(['success' => true]);
    }

    /**
     * Dos perfiles del mismo nivel que se contradicen sobre un concepto bloquean la
     * liquidación de ese concepto (PERFIL_AMBIGUO). Se detecta acá para que se vea
     * antes de liquidar y no en el recibo.
     *
     * @param  list<Perfil>  $perfiles
     * @return list<array<string, mixed>>
     */
    private function ambiguedades(array $perfiles): array
    {
        $fecha = new DateTimeImmutable;
        /** @var array<string, array<string, list<array{0: Perfil, 1: PerfilConcepto}>>> $porNivel */
        $porNivel = [];
        foreach ($perfiles as $p) {
            if (! $p->activo || ! $p->vigencia->contiene($fecha)) {
                continue;
            }
            foreach ($p->conceptos as $c) {
                if ($c->vigencia->contiene($fecha)) {
                    $porNivel[$c->codigoConcepto][$p->nivel->value.'|'.$this->claveAmbito($p)][] = [$p, $c];
                }
            }
        }

        $out = [];
        foreach ($porNivel as $concepto => $niveles) {
            foreach ($niveles as $clave => $candidatas) {
                if (count($candidatas) < 2) {
                    continue;
                }
                $firmas = array_unique(array_map(fn (array $x) => ($x[1]->aplica ? '1' : '0').'|'.($x[1]->variante ?? ''), $candidatas));
                if (count($firmas) > 1) {
                    $out[] = [
                        'concepto' => $concepto,
                        'nivel' => explode('|', $clave)[0],
                        'perfiles' => array_map(fn (array $x) => $x[0]->codigo, $candidatas),
                        'codigo' => ResolutorPerfiles::AMBIGUO,
                    ];
                }
            }
        }

        return $out;
    }

    private function claveAmbito(Perfil $p): string
    {
        return implode(':', [$p->idConvenio ?? '', $p->idCategoria ?? '', $p->idEmpresa ?? '', $p->relacionLaboralId ?? '']);
    }

    private function ambito(Perfil $p): ?string
    {
        $buscar = function (array $lista, ?int $id): ?string {
            foreach ($lista as $x) {
                if ($id !== null && (int) $x['value'] === $id) {
                    return $x['label'];
                }
            }

            return $id === null ? null : (string) $id;
        };
        $c = $this->catalogos();

        return match ($p->nivel) {
            TipoAmbito::General => 'Todos',
            TipoAmbito::Convenio => $buscar($c['convenios'], $p->idConvenio),
            TipoAmbito::Categoria => $buscar($c['categorias'], $p->idCategoria),
            TipoAmbito::Empresa => $buscar($c['empresas'], $p->idEmpresa),
            TipoAmbito::Trabajador => $buscar($c['relaciones'], $p->relacionLaboralId),
        };
    }

    /** @param array<string, mixed> $d */
    private function desdeDatos(array $d, ?Perfil $actual): Perfil
    {
        $nivel = TipoAmbito::from($d['nivel']);

        return new Perfil(
            $actual?->id,
            strtoupper($d['codigo'] ?? $actual?->codigo ?? ''),
            $d['nombre'],
            $nivel,
            Vigencia::de($d['desde'], $d['hasta'] ?? null),
            $nivel === TipoAmbito::Trabajador ? (int) $d['relacion_laboral_id'] : null,
            $nivel === TipoAmbito::Empresa ? (int) $d['empresa_id'] : null,
            $nivel === TipoAmbito::Categoria ? (int) $d['id_categoria'] : null,
            $nivel === TipoAmbito::Convenio ? (int) $d['id_convenio'] : null,
            (bool) ($d['activo'] ?? true),
            $actual?->conceptos ?? [],
            $d['observaciones'] ?: null,
        );
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Perfil $actual): array
    {
        $d = $request->validate([
            'codigo' => array_filter([
                $actual !== null ? 'nullable' : 'required',
                'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/',
                $actual === null ? 'unique:motor_perfil,codigo_perfil' : null,
            ]),
            'nombre' => 'required|string|max:150',
            'nivel' => 'required|in:GENERAL,CONVENIO,CATEGORIA,EMPRESA,TRABAJADOR',
            'id_convenio' => 'nullable|integer',
            'id_categoria' => 'nullable|integer',
            'empresa_id' => 'nullable|integer',
            'relacion_laboral_id' => 'nullable|integer|exists:motor_relacion_laboral,relacion_laboral_id',
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un perfil con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'hasta.after_or_equal' => 'RT-007: vigencia hasta no puede ser anterior a vigencia desde.',
        ]);

        $campo = match ($d['nivel']) {
            'CONVENIO' => 'id_convenio', 'CATEGORIA' => 'id_categoria',
            'EMPRESA' => 'empresa_id', 'TRABAJADOR' => 'relacion_laboral_id', default => null,
        };
        if ($campo !== null && empty($d[$campo])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $campo => "El nivel {$d['nivel']} exige indicar el ámbito correspondiente.",
            ]);
        }

        return $d;
    }

    /**
     * Las variantes que cada concepto tiene publicadas hoy. Es lo que el formulario
     * ofrece: sin esto habría que recordar de memoria si BASICO tiene
     * JORNADA_PACTADA o LARGA_DISTANCIA.
     *
     * @return array<string, list<string>>
     */
    private function variantesPublicadas(): array
    {
        $out = [];
        foreach ($this->formulas->vigentesA(new DateTimeImmutable) as $v) {
            /** @var VersionFormula $v */
            $out[$v->codigoConcepto][] = $v->variante;
        }
        foreach ($out as $codigo => $variantes) {
            $out[$codigo] = array_values(array_unique($variantes));
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function catalogos(): array
    {
        $variantes = $this->variantesPublicadas();

        return [
            'niveles' => array_map(fn (TipoAmbito $t) => [
                'value' => $t->value, 'label' => $t->etiqueta().' ('.Perfil::PRECEDENCIAS[$t->value].')',
            ], TipoAmbito::cases()),
            'conceptos' => MotorConcepto::query()->where('activo', true)->orderBy('codigo_concepto')
                ->get(['codigo_concepto', 'nombre_concepto', 'tipo_concepto', 'naturaleza'])
                ->map(fn ($c) => [
                    'value' => $c->codigo_concepto,
                    'label' => "{$c->codigo_concepto} — {$c->nombre_concepto}",
                    'tipo' => $c->tipo_concepto,
                    'naturaleza' => $c->naturaleza,
                    'variantes' => $variantes[$c->codigo_concepto] ?? [],
                ])->all(),
            'convenios' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorConvenios::class)->todos()),
            'categorias' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->descripcion}"], app(ProveedorCategorias::class)->todas()),
            'empresas' => array_map(fn ($x) => ['value' => $x->id, 'label' => "{$x->codigo} — {$x->razonSocial}"], app(ProveedorEmpresas::class)->todas()),
            'relaciones' => MotorRelacionLaboral::query()->orderBy('legajo')->get(['relacion_laboral_id', 'legajo', 'apellido_nombre'])
                ->map(fn ($r) => ['value' => $r->relacion_laboral_id, 'label' => "{$r->legajo} — {$r->apellido_nombre}"])->all(),
        ];
    }
}
