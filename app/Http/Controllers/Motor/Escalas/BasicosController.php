<?php

namespace App\Http\Controllers\Motor\Escalas;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\ProveedorCategorias;
use App\Motor\Aplicacion\Puertos\ProveedorConvenios;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBasico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Sueldos básicos por categoría (legacy) y vigencia. Un básico usado en una liquidación no se modifica: se abre una nueva vigencia. */
class BasicosController extends Controller
{
    public function __construct(private readonly ProveedorCategorias $categorias, private readonly ProveedorConvenios $convenios) {}

    public function index(Request $request): Response
    {
        $q = MotorBasico::query()->orderBy('codigo_categoria')->orderByDesc('vigencia_desde');
        if ($request->filled('categoria')) {
            $q->where('id_categoria', (int) $request->input('categoria'));
        }
        if ($request->filled('vigentes')) {
            $hoy = now()->format('Y-m-d');
            $q->where('vigencia_desde', '<=', $hoy)->where(fn ($w) => $w->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $hoy));
        }
        $items = $q->paginate(20)->withQueryString();
        $cats = $this->categorias->porIds($items->getCollection()->pluck('id_categoria')->unique()->all());
        $items->getCollection()->transform(fn ($b) => [
            'id' => $b->id_basico, 'id_categoria' => $b->id_categoria, 'categoria' => isset($cats[$b->id_categoria]) ? "{$cats[$b->id_categoria]->codigo} — {$cats[$b->id_categoria]->descripcion}" : $b->codigo_categoria,
            'convenio' => $cats[$b->id_categoria]->codigoConvenio ?? null, 'desde' => $b->vigencia_desde, 'hasta' => $b->vigencia_hasta, 'importe' => $b->importe_basico, 'estado' => $b->estado_validacion, 'fuente' => $b->fuente_normativa,
        ]);

        return Inertia::render('Motor/Escalas/Basicos/Index', ['items' => $items, 'filtros' => $request->only(['categoria', 'vigentes']), 'categorias' => $this->opcionesCategorias()]);
    }

    public function create(): Response
    {
        return Inertia::render('Motor/Escalas/Basicos/Form', ['basico' => null, 'categorias' => $this->opcionesCategorias()]);
    }

    public function edit(int $basico): Response
    {
        return Inertia::render('Motor/Escalas/Basicos/Form', ['basico' => MotorBasico::query()->findOrFail($basico), 'categorias' => $this->opcionesCategorias()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request, null);
        MotorBasico::query()->create($d + ['usuario_id' => auth()->id()]);

        return redirect()->route('motor.escalas.basicos.index')->with('success', 'Básico creado.');
    }

    public function update(Request $request, int $basico): RedirectResponse
    {
        $b = MotorBasico::query()->findOrFail($basico);
        $b->update($this->validar($request, $b));

        return redirect()->route('motor.escalas.basicos.index')->with('success', 'Básico actualizado.');
    }

    public function destroy(int $basico): RedirectResponse
    {
        MotorBasico::query()->whereKey($basico)->delete();

        return redirect()->route('motor.escalas.basicos.index')->with('success', 'Básico eliminado.');
    }

    private function validar(Request $request, ?MotorBasico $actual): array
    {
        $d = $request->validate([
            'id_categoria' => 'required|integer', 'vigencia_desde' => 'required|date_format:Y-m-d', 'vigencia_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:vigencia_desde',
            'importe_basico' => ['required', 'regex:/^\d{1,12}([.,]\d{1,6})?$/'], 'estado_validacion' => 'required|in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO', 'fuente_normativa' => 'nullable|string',
        ], ['id_categoria.required' => 'Elegí la categoría.', 'importe_basico.required' => 'El importe es obligatorio.', 'importe_basico.regex' => 'El importe debe ser numérico (hasta 6 decimales).', 'vigencia_hasta.after_or_equal' => 'Vigencia hasta no puede ser anterior a vigencia desde.']);
        $cat = $this->categorias->porIds([(int) $d['id_categoria']])[(int) $d['id_categoria']] ?? null;
        if ($cat === null) {
            abort(422, 'La categoría no existe en sue006s.');
        }
        // RT-007: sin superposición de vigencias para la misma categoría
        $q = MotorBasico::query()->where('id_categoria', $d['id_categoria'])->where('vigencia_desde', '<=', $d['vigencia_hasta'] ?? '9999-12-31')
            ->where(fn ($w) => $w->whereNull('vigencia_hasta')->orWhere('vigencia_hasta', '>=', $d['vigencia_desde']));
        if ($actual !== null) {
            $q->whereKeyNot($actual->id_basico);
        }
        if ($q->exists()) {
            abort(422, 'RT-007: ya existe un básico vigente para esa categoría en ese rango. Cerrá la vigencia anterior antes de abrir una nueva.');
        }
        $d['importe_basico'] = str_replace(',', '.', $d['importe_basico']);
        $d['codigo_categoria'] = $cat->codigo;

        return $d;
    }

    private function opcionesCategorias(): array
    {
        $conv = [];
        foreach ($this->convenios->todos() as $c) {
            $conv[$c->codigo] = $c->descripcion;
        }

        return array_map(fn ($c) => ['value' => $c->id, 'label' => "{$c->codigo} — {$c->descripcion}".($c->codigoConvenio !== null ? ' ('.($conv[$c->codigoConvenio] ?? $c->codigoConvenio).')' : '')], $this->categorias->todas());
    }
}
