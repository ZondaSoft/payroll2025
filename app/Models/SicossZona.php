<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicossZona extends Model
{
    protected $table = 'sicoss_zonas';

    protected $fillable = [
        'codigo',
        'numero',
        'detalle',
    ];
}
