<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Repositorios;

use App\Motor\Aplicacion\Puertos\RepositorioCatalogosArca;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RepositorioCatalogosArcaEloquent implements RepositorioCatalogosArca
{
    private const TABLAS = [
        'condiciones' => ['motor_condiciones_arca', 'codigo_condicion', 255, ['activo' => 1]],
        'modalidades' => ['motor_modalidades_arca', 'codigo_modalidad', 100, ['activo' => 1]],
        'situaciones' => ['motor_situaciones_revista_arca', 'codigo_situacion', 100, ['activo' => 1]],
        'actividades' => ['motor_actividades_arca', 'codigo_actividad_arca', 100, ['estado' => 'VIGENTE']],
        'localidades' => ['motor_localidades_arca', 'codigo_localidad_arca', 100, ['estado' => 'VIGENTE']],
    ];

    public function codigosCondiciones(): array
    {
        return $this->codigos('condiciones');
    }

    public function codigosModalidades(): array
    {
        return $this->codigos('modalidades');
    }

    public function codigosActividades(): array
    {
        return $this->codigos('actividades');
    }

    public function codigosLocalidades(): array
    {
        return $this->codigos('localidades');
    }

    public function codigosSituaciones(): array
    {
        return $this->codigos('situaciones');
    }

    public function insertarFaltantes(string $catalogo, array $filas): int
    {
        [$tabla, $pk, $largo, $extra] = self::TABLAS[$catalogo] ?? throw new InvalidArgumentException("Catálogo desconocido: {$catalogo}");
        $existentes = array_flip($this->codigos($catalogo));
        $ahora = now()->toDateTimeString();
        $n = 0;
        foreach ($filas as $f) {
            if ($f->codigo === '' || isset($existentes[$f->codigo])) {
                continue;
            }
            DB::table($tabla)->insertOrIgnore([$pk => $f->codigo, 'descripcion' => mb_substr($f->detalle, 0, $largo)] + $extra + ['created_at' => $ahora, 'updated_at' => $ahora]);
            $existentes[$f->codigo] = true;
            $n++;
        }

        return $n;
    }

    /** @return list<string> */
    private function codigos(string $catalogo): array
    {
        [$tabla, $pk] = self::TABLAS[$catalogo];

        return DB::table($tabla)->orderBy($pk)->pluck($pk)->map(fn ($v) => (string) $v)->all();
    }
}
