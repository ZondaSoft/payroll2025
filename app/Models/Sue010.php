<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue010 extends Model
{
    protected $fillable = [
        'detalle',
        'horas_semana',
        'parcial',
    ];

    protected $casts = [
        'horas_semana' => 'integer',
        'parcial' => 'boolean',
    ];
}
