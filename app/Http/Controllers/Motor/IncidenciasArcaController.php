<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioIncidenciasArca;
use App\Motor\Aplicacion\Puertos\RepositorioSubsistemas;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Matriz conceptos × 13 aplicaciones ARCA con edición inline (S/N/PENDIENTE). */
class IncidenciasArcaController extends Controller
{
    public function __construct(private readonly RepositorioConceptos $conceptos, private readonly RepositorioIncidenciasArca $incidencias, private readonly RepositorioSubsistemas $subsistemas, private readonly RepositorioVersiones $versiones) {}

    public function index(Request $request): Response
    {
        $hoy = now()->toDateTimeImmutable();
        $aplicaciones = array_map(fn ($a) => ['clave' => $a->clave(), 'subsistema' => $a->codigoSubsistema, 'tipo' => $a->tipoAplicacion->value, 'base' => $a->codigoBase, 'columna' => $a->columnaLsd], $this->subsistemas->aplicaciones());
        $todas = $this->incidencias->todasVigentes($hoy);
        $publicados = $this->versiones->vigentesA($hoy);
        $filas = [];
        foreach ($this->conceptos->todos(true) as $c) {
            if (! $c->esHaber()) {
                continue;
            }
            if ($request->filled('naturaleza') && $c->naturaleza->value !== $request->input('naturaleza')) {
                continue;
            }
            $codigo = (string) $c->codigo;
            $celdas = [];
            foreach ($todas[$codigo] ?? [] as $i) {
                $celdas[$i->claveAplicacion()] = ['id' => $i->id, 'incide' => $i->incidencia->value, 'estado' => $i->estadoValidacion->value];
            }
            $filas[] = ['codigo' => $codigo, 'nombre' => $c->nombre, 'naturaleza' => $c->naturaleza->value, 'codigo_afip' => $c->codigoAfip, 'publicado' => isset($publicados[$codigo]), 'celdas' => $celdas];
        }

        return Inertia::render('Motor/IncidenciasArca/Index', ['aplicaciones' => $aplicaciones, 'filas' => $filas, 'filtros' => $request->only(['naturaleza']),
            'empresas' => \Illuminate\Support\Facades\DB::table('sue086s')->orderBy('codigo')->get(['id', 'codigo', 'detalle'])->map(fn ($e) => ['value' => $e->id, 'label' => "{$e->codigo} — {$e->detalle}"])->all()]);
    }

    public function celda(Request $request): JsonResponse
    {
        $d = $request->validate(['codigo_concepto' => 'required|string|exists:motor_conceptos,codigo_concepto', 'clave' => 'required|string', 'incide' => 'required|in:S,N,PENDIENTE']);
        $ap = null;
        foreach ($this->subsistemas->aplicaciones() as $a) {
            if ($a->clave() === $d['clave']) {
                $ap = $a;
            }
        }
        if ($ap === null) {
            return response()->json(['message' => 'Aplicación inexistente.'], 422);
        }
        $prev = null;
        foreach ($this->incidencias->deConcepto($d['codigo_concepto']) as $i) {
            if ($i->claveAplicacion() === $d['clave'] && $i->vigencia->contiene(now()->toDateTimeImmutable())) {
                $prev = $i;
            }
        }
        $c = $this->conceptos->porCodigo(\App\Motor\Dominio\ValorObjetos\CodigoConcepto::de($d['codigo_concepto']));
        $i = $this->incidencias->guardar(new IncidenciaArca($prev?->id, $d['codigo_concepto'], $ap->id, $ap->codigoSubsistema, $ap->tipoAplicacion, Incidencia::from($d['incide']),
            $prev?->vigencia ?? Vigencia::abiertaDesde($c?->vigencia->desde ?? now()->toDateTimeImmutable()), EstadoValidacion::SinConfirmar), auth()->id());
        $publicado = isset($this->versiones->vigentesA(now()->toDateTimeImmutable())[$d['codigo_concepto']]);

        return response()->json(['success' => true, 'id' => $i->id, 'incide' => $i->incidencia->value, 'estado' => $i->estadoValidacion->value,
            'aviso' => $publicado ? 'El concepto está publicado: el cambio rige para el motor recién al publicar una nueva versión.' : null]);
    }
}
