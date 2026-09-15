<?php

namespace App\Http\Controllers\Motor\Catalogo;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD genérico para los catálogos del motor (estilo LsdTopeController):
 * index paginado + Form compartido create/edit + validación construida desde la
 * definición tipada. Catálogos "cerrados" (normativa que ninguna empresa crea ni
 * borra) solo permiten editar activo/estado_validacion. Borrado = baja lógica (RT-010).
 */
abstract class CatalogoMotorController extends Controller
{
    abstract protected function definicion(): DefinicionCatalogo;

    public function index(Request $request): Response
    {
        $d = $this->definicion();
        $q = $d->modelo::query();
        foreach ($d->filtros as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->input($f));
            }
        }
        if ($request->filled('buscar')) {
            $b = '%'.$request->input('buscar').'%';
            $q->where(function (Builder $w) use ($d, $b) {
                foreach ($d->campos as $c) {
                    if (in_array($c->tipo, ['texto', 'texto_largo'], true) && $c->enLista) {
                        $w->orWhere($c->nombre, 'like', $b);
                    }
                }
            });
        }
        foreach ($d->orden ?: [[$d->claveNatural, 'asc']] as [$col, $dir]) {
            $q->orderBy($col, $dir);
        }
        $this->alterarConsulta($q, $request);

        return Inertia::render($d->vista ?? 'Motor/Catalogo/Index', [
            'definicion' => $d->aArray(),
            'items' => $q->paginate(20)->withQueryString(),
            'filtros' => $request->only(array_merge($d->filtros, ['buscar'])),
            'opciones' => $this->opciones(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $d = $this->definicion();
        if ($d->cerrado) {
            return redirect()->route($d->ruta.'.index')->with('error', "El catálogo {$d->tituloPlural} es cerrado: no admite altas.");
        }

        return Inertia::render($d->vista ?? 'Motor/Catalogo/Form', ['definicion' => $d->aArray(), 'item' => null, 'opciones' => $this->opciones()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->definicion();
        if ($d->cerrado) {
            abort(403, 'Catálogo cerrado.');
        }
        $datos = $this->validar($request, null);
        $datos['usuario_id'] = auth()->id();
        $d->modelo::query()->create($this->antesDeGuardar($datos, null));

        return redirect()->route($d->ruta.'.index')->with('success', "{$d->titulo} creado correctamente.");
    }

    public function edit(string|int $id): Response
    {
        $d = $this->definicion();

        return Inertia::render($d->vista ?? 'Motor/Catalogo/Form', ['definicion' => $d->aArray(), 'item' => $d->modelo::query()->findOrFail($id), 'opciones' => $this->opciones()]);
    }

    public function update(Request $request, string|int $id): RedirectResponse
    {
        $d = $this->definicion();
        $m = $d->modelo::query()->findOrFail($id);
        $datos = $this->validar($request, $m);
        if ($d->cerrado) {
            $datos = array_intersect_key($datos, array_flip($d->editablesCerrado));
        }
        unset($datos[$d->claveNatural]); // los códigos naturales no se renombran (ON UPDATE RESTRICT)
        $m->update($this->antesDeGuardar($datos, $m));

        return redirect()->route($d->ruta.'.index')->with('success', "{$d->titulo} actualizado correctamente.");
    }

    public function destroy(string|int $id): RedirectResponse
    {
        $d = $this->definicion();
        $m = $d->modelo::query()->findOrFail($id);
        if ($d->cerrado) {
            abort(403, 'Catálogo cerrado.');
        }
        if ($d->bajaLogica && $d->campo('activo') !== null) {
            $m->update(['activo' => false]);
            $msg = "{$d->titulo} dado de baja (baja lógica: los registros usados no se borran).";
        } else {
            $m->delete();
            $msg = "{$d->titulo} eliminado correctamente.";
        }

        return redirect()->route($d->ruta.'.index')->with('success', $msg);
    }

    /** @return array<string, mixed> */
    protected function validar(Request $request, ?Model $actual): array
    {
        $d = $this->definicion();
        $rules = [];
        $messages = [];
        foreach ($d->campos as $c) {
            if ($c->soloLectura && $actual !== null) {
                continue;
            }
            $r = [$c->obligatorio ? 'required' : 'nullable'];
            $r = array_merge($r, match ($c->tipo) {
                'entero' => ['integer'],
                'decimal', 'porcentaje' => ['regex:/^-?\d{1,12}([.,]\d{1,6})?$/'],
                'booleano' => ['boolean'],
                'fecha' => ['date_format:Y-m-d'],
                'periodo' => ['regex:/^\d{4}-\d{2}$/'],
                'select' => [$c->opciones === null ? 'string' : 'in:'.implode(',', array_column($c->opciones, 'value'))],
                'estado_validacion' => ['in:SIN_DATO,SIN_CONFIRMAR,CONFIRMADO'],
                'texto_largo' => ['string'],
                default => ['string', 'max:255'],
            }, $c->reglas);
            if ($c->nombre === $d->claveNatural) {
                $tabla = (new $d->modelo)->getTable();
                $pk = (new $d->modelo)->getKeyName();
                $r[] = 'unique:'.$tabla.','.$c->nombre.($actual !== null ? ','.$actual->getKey().','.$pk : '');
            }
            $rules[$c->nombre] = $r;
            $messages[$c->nombre.'.required'] = "{$c->etiqueta} es obligatorio.";
            $messages[$c->nombre.'.unique'] = "Ya existe un registro con ese {$c->etiqueta}.";
            $messages[$c->nombre.'.regex'] = "{$c->etiqueta}: formato inválido.";
            $messages[$c->nombre.'.in'] = "{$c->etiqueta}: valor no permitido.";
            $messages[$c->nombre.'.date_format'] = "{$c->etiqueta}: la fecha debe ser aaaa-mm-dd.";
            foreach ($c->mensajes as $k => $v) {
                $messages[$c->nombre.'.'.$k] = $v;
            }
        }
        $rules = array_merge($rules, $this->reglasExtra($actual));
        $datos = $request->validate($rules, $messages);
        foreach ($d->campos as $c) {
            if (isset($datos[$c->nombre]) && in_array($c->tipo, ['decimal', 'porcentaje'], true)) {
                $datos[$c->nombre] = str_replace(',', '.', (string) $datos[$c->nombre]);
            }
            if ($c->tipo === 'booleano') {
                $datos[$c->nombre] = (bool) ($datos[$c->nombre] ?? false);
            }
            if ($c->nombre === $d->claveNatural && isset($datos[$c->nombre]) && is_string($datos[$c->nombre])) {
                $datos[$c->nombre] = strtoupper(trim($datos[$c->nombre]));
            }
        }

        return $datos;
    }

    /** Hook: reglas de validación adicionales (p. ej. no superposición de vigencias). @return array<string, mixed> */
    protected function reglasExtra(?Model $actual): array
    {
        return [];
    }

    /** Hook: transformar los datos antes de persistir. @return array<string, mixed> */
    protected function antesDeGuardar(array $datos, ?Model $actual): array
    {
        return $datos;
    }

    /** Hook: opciones dinámicas para selects (catálogos relacionados). @return array<string, list<array{value: mixed, label: string}>> */
    protected function opciones(): array
    {
        return [];
    }

    protected function alterarConsulta(Builder $q, Request $request): void {}

    /** Campos comunes reutilizables. */
    protected static function campoActivo(): CampoCatalogo
    {
        return new CampoCatalogo('activo', 'Activo', 'booleano', default: true, col: 3, ayuda: 'Habilitación lógica: FALSE lo mantiene histórico pero no disponible para nuevas asignaciones.');
    }

    protected static function campoEstadoValidacion(string $default = 'SIN_CONFIRMAR'): CampoCatalogo
    {
        return new CampoCatalogo('estado_validacion', 'Estado de validación', 'estado_validacion', obligatorio: true, default: $default, col: 3,
            ayuda: 'En PRODUCCIÓN solo CONFIRMADO habilita el cálculo; en PRUEBA, SIN_CONFIRMAR pasa con aviso.');
    }

    /** @return list<CampoCatalogo> */
    protected static function camposVigencia(bool $desdeObligatoria = true): array
    {
        return [
            new CampoCatalogo('vigencia_desde', 'Vigencia desde', 'fecha', obligatorio: $desdeObligatoria, col: 3),
            new CampoCatalogo('vigencia_hasta', 'Vigencia hasta', 'fecha', col: 3, ayuda: 'Vacío = vigencia abierta.'),
        ];
    }

    protected static function campoFuente(): CampoCatalogo
    {
        return new CampoCatalogo('fuente_normativa', 'Fuente normativa', 'texto_largo', enLista: false, col: 12);
    }
}
