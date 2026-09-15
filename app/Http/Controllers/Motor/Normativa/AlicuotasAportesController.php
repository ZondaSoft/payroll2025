<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorAlicuotaAporte;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseArca;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorCondicionArca;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorSubsistema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AlicuotasAportesController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorAlicuotaAporte::class, 'motor.normativa.alicuotas-aportes', 'Alícuota de aporte', 'Alícuotas de aportes personales', 'codigo', [
            new CampoCatalogo('codigo', 'Código', obligatorio: true, soloLectura: true, col: 3),
            new CampoCatalogo('id_subsistema', 'Subsistema', 'select', obligatorio: true, col: 3),
            new CampoCatalogo('codigo_condicion', 'Condición ARCA', 'select', col: 3, ayuda: 'Vacío = comodín (aplica a toda condición sin fila propia). Una fila específica siempre gana.'),
            new CampoCatalogo('base_aplicable', 'Base aplicable', 'select', obligatorio: true, col: 3),
            new CampoCatalogo('porcentaje', 'Porcentaje (fracción)', 'porcentaje', obligatorio: true, col: 3),
            new CampoCatalogo('aplica_tope', 'Aplica tope', 'booleano', default: true, col: 3),
            ...self::camposVigencia(), self::campoEstadoValidacion(), self::campoFuente(),
        ], orden: [['codigo', 'asc']], filtros: ['estado_validacion'],
            descripcion: 'Los aportes NO son parámetros del motor de fórmulas: son normativa. El 3 % de obra social no va acá (módulo de obra social).');
    }

    protected function opciones(): array
    {
        return [
            'id_subsistema' => MotorSubsistema::query()->whereNotNull('base_aportes')->orderBy('id_subsistema')->get()->map(fn ($s) => ['value' => $s->id_subsistema, 'label' => "{$s->codigo} — {$s->descripcion}"])->all(),
            'codigo_condicion' => MotorCondicionArca::query()->orderBy('codigo_condicion')->get()->map(fn ($c) => ['value' => $c->codigo_condicion, 'label' => "{$c->codigo_condicion} — {$c->descripcion}"])->all(),
            'base_aplicable' => MotorBaseArca::query()->where('sujeto_obligado', 'TRABAJADOR')->orderBy('id_base')->get()->map(fn ($b) => ['value' => $b->codigo_base, 'label' => "{$b->codigo_base} — {$b->descripcion}"])->all(),
        ];
    }

    protected function alterarConsulta(Builder $q, Request $request): void
    {
        $q->addSelect(['motor_alicuotas_aportes.*', 'codigo_subsistema' => MotorSubsistema::query()->select('codigo')->whereColumn('motor_subsistemas.id_subsistema', 'motor_alicuotas_aportes.id_subsistema')]);
    }

    protected function reglasExtra(?Model $actual): array
    {
        return ['id_subsistema' => ['required', 'integer', 'exists:motor_subsistemas,id_subsistema']];
    }
}
