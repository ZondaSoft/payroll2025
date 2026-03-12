<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SicossSinie extends Model
{
    use HasFactory;

    protected $table = 'sicoss_sinies';

    protected $fillable = [
        'codigo',
        'detalle',
    ];
}
