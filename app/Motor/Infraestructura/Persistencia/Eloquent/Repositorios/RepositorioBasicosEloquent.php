<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioBasicos;
use App\Motor\Dominio\Entidades\Basico;
use App\Motor\Infraestructura\Persistencia\Eloquent\Modelos\MotorBasico;
use DateTimeInterface;

final class RepositorioBasicosEloquent implements RepositorioBasicos
{
    public function porCategorias(array $idsCategoria, DateTimeInterface $fecha): array
    {
        if ($idsCategoria === []) {
            return [];
        }
        $out = [];
        foreach (Mapeos::vigenteA(MotorBasico::query()->whereIn('id_categoria', $idsCategoria), $fecha)->get() as $f) {
            $out[(int) $f->id_categoria][] = $this->mapear($f);
        }

        return $out;
    }

    public function todos(): array
    {
        return MotorBasico::query()->orderBy('id_categoria')->orderByDesc('vigencia_desde')->get()->map(fn ($f) => $this->mapear($f))->all();
    }

    public function guardar(Basico $b, ?int $usuarioId = null): Basico
    {
        $datos = ['id_categoria' => $b->idCategoria, 'codigo_categoria' => $b->codigoCategoria, 'importe_basico' => (string) $b->importe,
            'estado_validacion' => $b->estadoValidacion->value] + Mapeos::columnasVigencia($b->vigencia);
        if ($b->id === null) {
            $m = MotorBasico::query()->create($datos + ['usuario_id' => $usuarioId]);
        } else {
            $m = MotorBasico::query()->findOrFail($b->id);
            $m->update($datos);
        }

        return new Basico((int) $m->id_basico, $b->idCategoria, $b->codigoCategoria, $b->vigencia, $b->importe, $b->estadoValidacion);
    }

    private function mapear(object $f): Basico
    {
        return new Basico((int) $f->id_basico, (int) $f->id_categoria, $f->codigo_categoria, Mapeos::vigencia($f), Mapeos::importe($f->importe_basico), Mapeos::estado($f));
    }
}
