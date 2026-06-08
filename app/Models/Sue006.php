<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue006 extends Model
{
    protected $table = 'sue006s';

    protected $fillable = [
        'codigo',
        'detalle',
        'sue_bas',
        'hsnormal',
        'hsmin',
        'hsmax',
        'cod_conve',
    ];

    protected $casts = [
        'sue_bas'  => 'integer',
        'hsnormal' => 'integer',
        'hsmin'    => 'integer',
        'hsmax'    => 'integer',
    ];
}
