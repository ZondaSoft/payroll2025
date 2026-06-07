<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LsdTope extends Model
{
    protected $table = 'lsd_topes';

    protected $fillable = [
        'periodo_desde',
        'tope_aportes',
        'base_minima',
        'normativa',
        'ipc_porcentaje',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'tope_aportes' => 'decimal:2',
        'base_minima' => 'decimal:2',
        'ipc_porcentaje' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Devuelve el tope vigente para un período YYYYMM dado.
     * Es el registro con el periodo_desde más reciente que sea <= al período buscado.
     */
    public static function vigenteParaPeriodo(string $periodoYYYYMM): ?self
    {
        return static::where('periodo_desde', '<=', $periodoYYYYMM)
            ->orderByDesc('periodo_desde')
            ->first();
    }
}
