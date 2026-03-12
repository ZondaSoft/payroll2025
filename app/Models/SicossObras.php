<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicossObras extends Model
{
    protected $table = 'sicoss_obras';

    protected $fillable = [
        'codigo',
        'detalle',
    ];
}
