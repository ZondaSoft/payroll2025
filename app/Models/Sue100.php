<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue100 extends Model
{
    protected $table = 'sue100s';

    protected $fillable = [
        'periodo',
        'tipoliq',
        'fecha',
        'comentarios',
        'user',
        'legajos',
        'nombrearchivo',
        'tamanio',
        'estado',
        'fecha_pago',
        'banco',
        'fecha_aportes',
        'periodo_aporte',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_pago' => 'date',
        'fecha_aportes' => 'date',
    ];
}
