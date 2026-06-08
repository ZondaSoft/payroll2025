<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue015 extends Model
{
    protected $table = 'sue015s';

    protected $fillable = [
        'codigo',
        'detalle',
        'localidad',
        'cp',
        'tel1',
        'tel2',
        'tel3',
        'email',
        'web',
        'contacto',
        'porce_con',
        'porce_apo',
        'fijo_apo',
        'fijo_con',
    ];

    protected $casts = [
        'porce_con' => 'decimal:2',
        'porce_apo' => 'decimal:2',
        'fijo_apo'  => 'decimal:2',
        'fijo_con'  => 'decimal:2',
    ];
}
