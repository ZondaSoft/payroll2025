<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LsdImporteDetraer extends Model
{
    protected $table = 'lsd_importes_detraer';

    protected $fillable = [
        'periodo_desde',
        'importe',
        'importe_sac',
        'normativa',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'importe_sac' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Devuelve el importe vigente para un período YYYYMM dado.
     * Es el registro con el periodo_desde más reciente que sea <= al período buscado.
     */
    public static function vigenteParaPeriodo(string $periodoYYYYMM): ?self
    {
        return static::where('periodo_desde', '<=', $periodoYYYYMM)
            ->orderByDesc('periodo_desde')
            ->first();
    }
}
