<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLiquidacionOk extends Model
{
    use HasFactory;

    protected $fillable = [
        'registro',
        'periodo',
        'legajo',
        'descripcion',
        'importe',
        'detalle',
    ];
}
