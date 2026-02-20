<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicossConceptosArca extends Model
{
    protected $fillable = ['codigo', 'descripcion'];

    protected $table = 'sicoss_conceptos_arcas';
}
