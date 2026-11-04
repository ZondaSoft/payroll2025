<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros generales del sistema (tabla sue_config).
 * Es un registro único: se accede siempre con SueConfig::actual().
 */
class SueConfig extends Model
{
    protected $table = 'sue_config';

    protected $fillable = [
        'lsd_no_conceptos',
        'lsd_ocultar_activos',
        'usuario_id',
    ];

    protected $casts = [
        'lsd_no_conceptos' => 'boolean',
        'lsd_ocultar_activos' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** El único registro de parámetros; lo crea con los valores por defecto si todavía no existe. */
    public static function actual(): self
    {
        return static::query()->orderBy('id')->firstOrCreate([], [
            'lsd_no_conceptos' => false,
            // El diálogo de activos sin liquidación se muestra por defecto: ocultarlo
            // deja gente afuera del TXT, así que tiene que ser una decisión explícita.
            'lsd_ocultar_activos' => false,
        ]);
    }
}
