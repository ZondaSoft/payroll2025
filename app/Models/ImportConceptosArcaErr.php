<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportConceptosArcaErr extends Model
{
    use HasFactory;

    protected $table = 'import_conceptos_arca_errs';

    protected $fillable = [
        'id_empresa',
        'nombre_archivo',
        'tamanio_archivo',
        'detalle',
        'observaciones',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
