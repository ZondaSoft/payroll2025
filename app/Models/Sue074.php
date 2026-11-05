<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sue074 extends Model
{
    use HasFactory;

    protected $fillable = [
        'legajo_codigo',
        'periodo',
        'categoria_campo',
        'acontecimiento',
        'usuario',
        'dato_original',
        'dato_nuevo',
    ];
}
