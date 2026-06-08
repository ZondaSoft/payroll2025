<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue107 extends Model
{
    protected $table = 'sue107s';

    protected $fillable = [
        'codigo',
        'detalle',
        'duracion',
        'aviso',
    ];

    protected $casts = [
        'codigo'   => 'integer',
        'duracion' => 'integer',
        'aviso'    => 'integer',
    ];
}
