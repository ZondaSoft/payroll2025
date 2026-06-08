<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue030 extends Model
{
    protected $table = 'sue030s';

    protected $fillable = [
        'codigo',
        'detalle',
        'responsa',
        'domicilio',
        'localidad',
    ];
}
