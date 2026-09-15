<?php

namespace App\Motor\Infraestructura\Persistencia\Eloquent\Modelos;

use Illuminate\Database\Eloquent\Model;

/**
 * Base de los modelos Eloquent del Motor.
 *
 * Son solo un mecanismo de persistencia: nunca salen de la capa de
 * Infraestructura (los repositorios devuelven entidades de Dominio), no
 * llevan lógica de negocio ni relaciones cargadas en loop, y no usan casts
 * `decimal:` (los importes viajan como string y se convierten a BigDecimal
 * en el repositorio). $guarded vacío porque solo los escriben los repositorios
 * con datos ya validados, nunca un Request.
 */
abstract class ModeloMotor extends Model
{
    protected $guarded = [];
}
