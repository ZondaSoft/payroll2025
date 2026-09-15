<?php

namespace App\Http\Controllers\Motor\Normativa;

use App\Http\Controllers\Motor\Catalogo\CampoCatalogo;
use App\Http\Controllers\Motor\Catalogo\CatalogoMotorController;
use App\Http\Controllers\Motor\Catalogo\DefinicionCatalogo;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBaseArca;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorComponenteTope;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorTopeMinimo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TopesController extends CatalogoMotorController
{
    protected function definicion(): DefinicionCatalogo
    {
        return new DefinicionCatalogo(MotorTopeMinimo::class, 'motor.normativa.topes', 'Tope', 'Topes mínimos y máximos por período', 'codigo', [
            new CampoCatalogo('codigo', 'Código', obligatorio: true, soloLectura: true, col: 3, ayuda: 'TOP-008…'),
            new CampoCatalogo('periodo', 'Período', 'periodo', obligatorio: true, col: 3, ayuda: 'AAAA-MM. No hay arrastre: cada período necesita su propio tope.'),
            new CampoCatalogo('codigo_base', 'Base', 'select', obligatorio: true, col: 3),
            new CampoCatalogo('tipo_tope', 'Tipo', 'select', obligatorio: true, opciones: [['value' => 'MAXIMO', 'label' => 'Máximo'], ['value' => 'MINIMO', 'label' => 'Mínimo']], col: 3),
            new CampoCatalogo('componente', 'Componente', 'select', obligatorio: true, default: 'MENSUAL', col: 3),
            new CampoCatalogo('importe', 'Importe', 'decimal', col: 3, ayuda: 'Vacío = SIN_DATO: el motor bloquea (regla C), nunca lo interpreta como cero.'),
            self::campoEstadoValidacion(), self::campoFuente(),
        ], orden: [['periodo', 'desc'], ['codigo_base', 'asc'], ['tipo_tope', 'asc']], filtros: ['periodo', 'codigo_base', 'estado_validacion'],
            descripcion: 'Solo se topean REM_1, REM_4 y REM_5. Clave única: período + base + tipo + componente. Un tope usado en una liquidación no se modifica.');
    }

    protected function opciones(): array
    {
        return [
            'codigo_base' => MotorBaseArca::query()->where('admite_tope', 1)->orderBy('id_base')->get()->map(fn ($b) => ['value' => $b->codigo_base, 'label' => "{$b->codigo_base} — {$b->descripcion}"])->all(),
            'componente' => MotorComponenteTope::query()->where('activo', 1)->get()->map(fn ($c) => ['value' => $c->codigo_componente, 'label' => $c->descripcion])->all(),
        ];
    }

    protected function reglasExtra(?Model $actual): array
    {
        $unique = Rule::unique('motor_topes_minimos')->where(fn ($q) => $q->where('periodo', request('periodo'))->where('codigo_base', request('codigo_base'))->where('tipo_tope', request('tipo_tope')));
        if ($actual !== null) {
            $unique->ignore($actual->getKey(), 'id_tope');
        }

        return ['componente' => ['required', $unique]];
    }

    protected function antesDeGuardar(array $datos, ?Model $actual): array
    {
        if (($datos['importe'] ?? null) === null || $datos['importe'] === '') {
            $datos['importe'] = null;
            $datos['estado_validacion'] = 'SIN_DATO';
        }

        return $datos;
    }
}
