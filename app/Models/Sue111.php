<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documentos digitalizados del legajo (tabla del portal RRHH Drakma, base compartida).
 *
 * Los de la ficha del empleado van en la carpeta 3 y `origen` dice qué son
 * (Foto, DNI, ALTA, BAJA, CUIL, Otro). `nom_arch` es relativo al disco `portal`.
 */
class Sue111 extends Model
{
    /** Carpeta de las imágenes/documentos de la ficha del legajo en el portal. */
    public const CARPETA_LEGAJO = 3;

    protected $fillable = ['legajo', 'concepto', 'fecha', 'fecha_alta', 'nom_arch', 'carpeta', 'origen',
        'distribucion', 'codsector', 'tipo_publicacion', 'enviar_portal', 'solicitar_firma', 'user_id'];

    protected $casts = [
        'enviar_portal' => 'boolean',
        'solicitar_firma' => 'boolean',
    ];
}
