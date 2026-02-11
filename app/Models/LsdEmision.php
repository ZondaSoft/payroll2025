<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LsdEmision extends Model
{
    use HasFactory;

    protected $table = 'lsd_emisiones';

    protected $fillable = [
        'id_empresa',
        'numero_emision',
        'fecha_emision',
        'periodo_desde',
        'periodo_hasta',
        'cantidad_empleados',
        'monto_total',
        'estado',
        'observaciones',
        'usuario_id',
        'fecha_generacion',
        'fecha_envio',
        'archivo_pdf',
        'archivo_xml',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'periodo_desde' => 'date',
        'periodo_hasta' => 'date',
        'fecha_generacion' => 'datetime',
        'fecha_envio' => 'datetime',
        'monto_total' => 'decimal:2',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Sue086::class, 'id_empresa');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(LsdItem::class, 'lsd_periodo_id');
    }

    // Scopes
    public function scopeByEmpresa($query, $idEmpresa)
    {
        return $query->where('id_empresa', $idEmpresa);
    }

    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeByPeriodo($query, $desde, $hasta)
    {
        return $query->whereBetween('periodo_desde', [$desde, $hasta]);
    }
}
