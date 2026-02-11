<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LsdItem extends Model
{
    protected $table = 'lsd_items';

    protected $fillable = [
        'lsd_emision_id',
        'cuil',
        'legajo',
        'dependencia',
        'cbu',
        'fecha_pago',
        'forma_pago',
        'codigo_concepto',
        'cantidad',
        'unidades',
        'importe',
        'debito_credito',
        'periodo_ajuste',
        'conyugue',
        'hijos',
        'marca_cct',
        'marca_scvo',
        'corr_reduccion',
        'tipo_empresa',
        'tipo_operacion',
        'situacion',
        'condicion',
        'actividad',
        'modalidad',
        'siniestro',
        'localidad',
        'revista1',
        'dia_revista1',
        'revista2',
        'dia_revista2',
        'revista3',
        'dia_revista3',
        'dias_trabajados',
        'horas_trabajadas',
        'porc_aporte_adic_ss',
        'contr_tarea',
        'cod_os',
        'adherentes',
        'aporte_adic_os',
        'contrib_adic_os',
        'base_calculo',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'cantidad' => 'decimal:2',
        'importe' => 'decimal:2',
        'porc_aporte_adic_ss' => 'decimal:2',
        'contr_tarea' => 'decimal:2',
        'aporte_adic_os' => 'decimal:2',
        'contrib_adic_os' => 'decimal:2',
        'base_calculo' => 'decimal:2',
    ];

    /**
     * Relación con LsdEmision
     */
    public function lsdEmision()
    {
        return $this->belongsTo(LsdEmision::class, 'lsd_periodo_id');
    }
}
