<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Histórico de correcciones automáticas sobre la liquidación. Cualquier proceso que modifique un importe
 * liquidado debe registrar acá el cambio con su motivo y origen, vía LiquidacionCorreccion::registrar(...).
 */
class LiquidacionCorreccion extends Model
{
    protected $table = 'liquidacion_correcciones';

    protected $fillable = [
        'periodo',
        'tipoliq',
        'legajo',
        'cuil',
        'concepto',
        'concepto_arca',
        'sue090_id',
        'importe_anterior',
        'importe_nuevo',
        'motivo',
        'origen',
        'usuario_id',
    ];

    protected $casts = [
        'importe_anterior' => 'decimal:2',
        'importe_nuevo' => 'decimal:2',
    ];

    /**
     * Registra una corrección en el histórico. `usuario_id` se completa con el usuario autenticado si no
     * se pasa. Pensado para llamarse desde cualquier punto que corrija la liquidación.
     */
    public static function registrar(array $datos): self
    {
        $datos['usuario_id'] = $datos['usuario_id'] ?? auth()->id();
        return static::create($datos);
    }
}
