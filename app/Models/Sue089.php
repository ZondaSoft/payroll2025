<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sue089 extends Model
{
    protected $fillable = [
        'desde',
        'hasta',
        'tiporem',
    ];

    protected $casts = [
        'desde' => 'integer',
        'hasta' => 'integer',
    ];
}
