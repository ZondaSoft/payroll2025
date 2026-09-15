<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAlicuotaContribucion;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseArca;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AlicuotasContribucionesController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorAlicuotaContribucion::class, 'motor.normativa.alicuotas-contribuciones', 'Alícuota de contribución', 'Alícuotas de contribuciones patronales', 'codigo', [
            new CampoCatalogo('codigo', 'Código', obligatorio: true, soloLectura: true, col: 3, ayuda: 'Identificador estable (ALIC-009…).'),
            new CampoCatalogo('encuadramiento', 'Encuadramiento', 'select', obligatorio: true, opciones: [['value' => 'A', 'label' => 'A (20,40 %)'], ['value' => 'B', 'label' => 'B (18 %)']], col: 3),
            new CampoCatalogo('id_subsistema', 'Subsistema', 'select', obligatorio: true, col: 3),
            new CampoCatalogo('base_aplicable', 'Base aplicable', 'select', obligatorio: true, col: 3),
            new CampoCatalogo('porcentaje', 'Porcentaje (fracción)', 'porcentaje', obligatorio: true, col: 3, ayuda: '10,77 % se carga como 0.1077.'),
            ...self::camposVigencia(), self::campoEstadoValidacion(), self::campoFuente(),
        ], orden: [['encuadramiento', 'asc'], ['codigo', 'asc']], filtros: ['encuadramiento', 'estado_validacion'],
            descripcion: 'Cuando cambia la norma no se edita la fila: se cierra la vigente y se agrega una nueva. La OS (6 %) no va acá.');
    }

    protected function opciones(): array
    {
        return [
            'id_subsistema' => MotorSubsistema::query()->orderBy('id_subsistema')->get()->map(fn ($s) => ['value' => $s->id_subsistema, 'label' => "{$s->codigo} — {$s->descripcion}"])->all(),
            'base_aplicable' => MotorBaseArca::query()->orderBy('id_base')->get()->map(fn ($b) => ['value' => $b->codigo_base, 'label' => "{$b->codigo_base} — {$b->descripcion}"])->all(),
        ];
    }

    protected function alterarConsulta(Builder $q, Request $request): void
    {
        $q->addSelect(['motor_alicuotas_contribuciones.*', 'codigo_subsistema' => MotorSubsistema::query()->select('codigo')->whereColumn('motor_subsistemas.id_subsistema', 'motor_alicuotas_contribuciones.id_subsistema')]);
    }

    protected function reglasExtra(?Model $actual): array
    {
        return ['id_subsistema' => ['required', 'integer', 'exists:motor_subsistemas,id_subsistema']];
    }
}
