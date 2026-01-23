<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLiquidacionErr extends Model
{
    use HasFactory;

    protected $fillable = [
        'registro',
        'detalle',
    ];
}
