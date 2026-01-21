<?php

namespace App\Observers;

use Illuminate\Support\Carbon;
use App\Models\Sue001;
use App\Models\Sue074;
use Illuminate\Support\Facades\Auth;

class Sue001Observer
{
    /**
     * Handle the Sue001 "created" event.
     */
    public function created(Sue001 $legajo): void
    {
        Sue074::create([
            'legajo_codigo' => $legajo->codigo,
            'acontecimiento' => 'Alta de legajo',
            'usuario'        => Auth::user()->name ?? 'sistema',
            'dato_original'  => null,
            'dato_nuevo'     => 'Legajo creado',
        ]);
    }

    /**
     * Handle the Sue001 "updated" event.
     */
    public function updated(Sue001 $legajo): void
    {
        $dirty = $legajo->getDirty();

        // Campos de "fecha" que deben compararse sin hora
        $dateOnly = ['alta','fecha_naci','ultima_act','fecha_vto'];

        foreach ($dirty as $field => $newVal) {
            if (in_array($field, ['updated_at','created_at','deleted_at'], true)) {
                continue; // saltar timestamps
            }

            $oldVal = $legajo->getOriginal($field);

            // Normalizador de fechas (devuelve null si no hay valor)
            $normDate = function ($v) {
                if ($v === null || $v === '') return null;
                return $v instanceof Carbon
                    ? $v->toDateString()
                    : Carbon::parse($v)->toDateString();
            };

            // Si es un campo de fecha, normalizo ambos lados a Y-m-d
            if (in_array($field, $dateOnly, true)) {
                $oldCmp = $normDate($oldVal);
                $newCmp = $normDate($newVal);

                // Si son iguales como fecha (ignorando hora), saltamos
                if ($oldCmp === $newCmp) {
                    continue;
                }

                // Para guardar en historial, guardo ya normalizado
                $oldVal = $oldCmp;
                $newVal = $newCmp;
            } else {
                // Normalización simple para otros tipos
                if (is_bool($oldVal)) $oldVal = $oldVal ? '1' : '0';
                if (is_bool($newVal)) $newVal = $newVal ? '1' : '0';
            }

            // Registrar en sue074s
            Sue074::create([
                'legajo_codigo'  => $legajo->codigo,
                'acontecimiento' => "Cambio en {$field}",
                'usuario'        => Auth::user()->name ?? 'sistema',
                'dato_original'  => is_scalar($oldVal) ? (string)$oldVal : json_encode($oldVal),
                'dato_nuevo'     => is_scalar($newVal) ? (string)$newVal : json_encode($newVal),
            ]);
        }
    }

    /**
     * Handle the Sue001 "deleted" event.
     */
    public function deleted(Sue001 $legajo): void
    {
        Sue074::create([
            'legajo_codigo' => $legajo->codigo,
            'acontecimiento' => 'Baja de legajo',
            'usuario'        => Auth::user()->name ?? 'sistema',
            'dato_original'  => null,
            'dato_nuevo'     => 'Legajo eliminado',
        ]);
    }

    /**
     * Handle the Sue001 "restored" event.
     */
    public function restored(Sue001 $legajo): void
    {
        Sue074::create([
            'legajo_codigo' => $legajo->codigo,
            'acontecimiento' => 'Restauración de legajo',
            'usuario'        => Auth::user()->name ?? 'sistema',
            'dato_original'  => null,
            'dato_nuevo'     => 'Legajo restaurado',
        ]);
    }

    /**
     * Handle the Sue001 "force deleted" event.
     */
    public function forceDeleted(Sue001 $legajo): void
    {
        Sue074::create([
            'legajo_codigo' => $legajo->codigo,
            'acontecimiento' => 'Baja de legajo',
            'usuario'        => Auth::user()->name ?? 'sistema',
            'dato_original'  => null,
            'dato_nuevo'     => 'Legajo eliminado',
        ]);
    }
}
