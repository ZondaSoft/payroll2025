<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue054 extends Model
{
    protected $table = 'sue054s';

    protected $fillable = [
        'codigo',
        'detalle',
        'encargado',
    ];

    protected $casts = [
        'encargado' => 'integer',
    ];
}
