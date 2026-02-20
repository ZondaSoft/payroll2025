<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue103 extends Model
{
    protected $fillable = [
        'tipo',
        'desde',
        'hasta',
    ];

    protected $casts = [
        'tipo' => 'integer',
        'desde' => 'integer',
        'hasta' => 'integer',
    ];
}
